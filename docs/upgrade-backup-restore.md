# Upgrade, Backup, And Restore Runbook

This runbook covers Moodle upgrade events for both the local Docker stack and AWS server environments.

## Scope And Rules

- Use only official Moodle Git tags, for example `v5.2.1`.
- Do not upgrade to `main`, beta, or release-candidate tags for shared environments.
- Keep Moodle custom code in `moodle-overrides/`; do not treat the ignored `moodle/` checkout as source of truth.
- Back up both database and `moodledata` before upgrade.
- Validate plugin/theme compatibility before major upgrades.
- Do not use SSH for AWS server operations. Use GitHub Actions, Terraform, AWS CLI, ECS Exec, RDS, EFS, and AWS Backup.

## Quick Commands

| Event | Local Docker | Server/AWS |
| --- | --- | --- |
| Check current Moodle tag | `git -C moodle describe --tags --always --dirty` | Check deployed image tag in GitHub Actions/ECS task definition |
| Check official tags | `git ls-remote --tags --refs https://github.com/moodle/moodle.git 'v5.2*'` | Same command before changing CI `MOODLE_VERSION` |
| Backup | `make backup` | Run `Server Backup` workflow or create RDS snapshot plus EFS AWS Backup recovery point |
| Upgrade | `./scripts/update-moodle.sh v5.2.x` | Run `Moodle Version Upgrade` workflow for the target environment |
| Local auto rollback | `./scripts/update-moodle.sh --restore-on-fail v5.2.x` | Do not auto-rollback prod; use approved restore process |
| Restore | `./scripts/restore-backup.sh backups/YYYYMMDD-HHMMSS --yes` | Run `Server Restore` workflow for app rollback, then restore RDS/EFS through controlled AWS cutover when data changed |

## Local Docker Upgrade Event

### 1. Preflight

Check the current version:

```bash
git -C moodle describe --tags --always --dirty
rg '^MOODLE_VERSION=' .env
docker compose exec moodle php admin/cli/cfg.php --name=release
```

Check available official tags:

```bash
git ls-remote --tags --refs https://github.com/moodle/moodle.git 'v5.2*'
```

Check for old stacks or port conflicts:

```bash
docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | rg 'moodle_latest|elearn_mindset|5440|NAMES'
lsof -nP -iTCP:5440 -sTCP:LISTEN || true
```

The active local project must be `elearn_mindset`. If an old `moodle_latest_*` stack owns port `5440`, stop it before backing up this project:

```bash
docker stop moodle_latest_db moodle_latest_app moodle_latest_cron moodle_latest_redis moodle_latest_mailpit
```

Confirm the selected database has Moodle tables:

```bash
docker compose up -d db
docker compose exec -T db psql -U moodle -d moodle -tAc \
  "select count(*) from information_schema.tables where table_schema='public' and table_name like 'mdl_%';"
```

### 2. Backup

```bash
make backup
```

The backup script writes:

```text
backups/YYYYMMDD-HHMMSS/postgres.sql
backups/YYYYMMDD-HHMMSS/moodledata.tar.gz
backups/YYYYMMDD-HHMMSS/moodle-version.txt
```

It refuses to back up a database with zero `mdl_` tables unless explicitly overridden:

```bash
ALLOW_EMPTY_MOODLE_BACKUP=true make backup
```

Use that override only for a deliberately empty pre-install environment.

### 3. Upgrade

```bash
./scripts/update-moodle.sh v5.2.x
```

For local testing, allow automatic restore if the upgrade fails after backup:

```bash
./scripts/update-moodle.sh --restore-on-fail v5.2.x
```

The script:

1. Confirms the tag exists upstream.
2. Creates a backup.
3. Enables Moodle maintenance mode if Moodle is running.
4. Checks out the target tag in `moodle/`.
5. Syncs `moodle-overrides/`.
6. Updates `.env` with `MOODLE_VERSION`.
7. Runs Composer install.
8. Runs `admin/cli/upgrade.php`.
9. Purges caches.
10. Disables maintenance mode and starts cron.

### 4. Verify

```bash
git -C moodle describe --tags --always --dirty
rg '^MOODLE_VERSION=' .env
docker compose exec moodle php admin/cli/cfg.php --name=release
docker compose exec moodle php admin/cli/checks.php
docker compose exec moodle php admin/cli/check_database_schema.php
docker compose exec moodle php admin/cli/cron.php --keep-alive=0
curl -fsS --max-time 30 http://localhost:8080/ >/tmp/moodle-home.html
```

### 5. Local Restore

List backups:

```bash
ls -lt backups
```

Inspect a backup:

```bash
cat backups/YYYYMMDD-HHMMSS/moodle-version.txt
rg 'CREATE TABLE .*mdl_' backups/YYYYMMDD-HHMMSS/postgres.sql | head
```

Restore:

```bash
./scripts/restore-backup.sh backups/YYYYMMDD-HHMMSS --yes
```

or:

```bash
make restore BACKUP_DIR=backups/YYYYMMDD-HHMMSS
```

The restore is destructive for the local Docker database and `moodledata/`. It recreates the database, replaces `moodledata/`, checks out the recorded Moodle tag when available, syncs overrides, purges caches, disables maintenance mode, and restarts cron.

## Server/AWS Upgrade Event

The Terraform stack deploys Moodle on ECS Fargate, PostgreSQL on RDS, and `moodledata` on EFS. Server operations use AWS APIs and GitHub Actions, not SSH.

### 1. Set Environment Variables

```bash
export ENVIRONMENT=dev
export PROJECT_NAME=elearn-mindset
export AWS_REGION=us-west-2
export AWS_ACCOUNT_ID="$(aws sts get-caller-identity --query Account --output text)"

export CLUSTER="${PROJECT_NAME}-${ENVIRONMENT}-cluster"
export SERVICE="${PROJECT_NAME}-${ENVIRONMENT}-service"
export CRON_SERVICE="${PROJECT_NAME}-${ENVIRONMENT}-cron"
export DB_INSTANCE="${PROJECT_NAME}-${ENVIRONMENT}-postgres"
export STAMP="$(date +%Y%m%d-%H%M%S)"
```

Use `stage` or `prod` for shared environments.

### 2. Server Preflight

Confirm AWS identity and ECS state:

```bash
aws sts get-caller-identity
aws ecs describe-services \
  --cluster "${CLUSTER}" \
  --services "${SERVICE}" "${CRON_SERVICE}" \
  --query 'services[].{name:serviceName,status:status,desired:desiredCount,running:runningCount,taskDefinition:taskDefinition}'
```

Record the active task definition before upgrade:

```bash
aws ecs describe-services \
  --cluster "${CLUSTER}" \
  --services "${SERVICE}" \
  --query 'services[0].taskDefinition' \
  --output text
```

Check the target Moodle tag exists:

```bash
git ls-remote --tags --refs https://github.com/moodle/moodle.git 'v5.2*'
```

Check Terraform outputs:

```bash
terraform -chdir="terraform/envs/${ENVIRONMENT}" output
```

### 3. Server Backup

Preferred manual pipeline path:

1. Open GitHub Actions.
2. Select `Server Backup`.
3. Click `Run workflow`.
4. Select `target_environment`: `dev`, `stage`, or `prod`.
5. Leave `wait_for_completion=true` for upgrade events.
6. Supply `backup_vault_name` and `backup_role_arn`, or configure repository variables:

   ```text
   BACKUP_VAULT_NAME=<aws-backup-vault-name>
   BACKUP_ROLE_ARN=<aws-backup-service-role-arn>
   ```

7. Wait for the workflow summary and record the RDS snapshot ID plus EFS backup job ID.

The backup workflow uses GitHub OIDC, reads Terraform remote state, creates the RDS snapshot, starts the EFS AWS Backup job, and writes the backup metadata to the workflow summary.

Manual AWS CLI path:

Create an RDS snapshot:

```bash
export DB_SNAPSHOT="${PROJECT_NAME}-${ENVIRONMENT}-pre-upgrade-${STAMP}"

aws rds create-db-snapshot \
  --db-instance-identifier "${DB_INSTANCE}" \
  --db-snapshot-identifier "${DB_SNAPSHOT}"

aws rds wait db-snapshot-available \
  --db-snapshot-identifier "${DB_SNAPSHOT}"
```

Create an EFS recovery point with AWS Backup. This requires a pre-existing AWS Backup vault and backup IAM role:

```bash
export BACKUP_VAULT_NAME=<backup-vault-name>
export BACKUP_ROLE_ARN=<aws-backup-service-role-arn>
export EFS_ID="$(terraform -chdir="terraform/envs/${ENVIRONMENT}" output -raw efs_file_system_id)"
export EFS_ARN="arn:aws:elasticfilesystem:${AWS_REGION}:${AWS_ACCOUNT_ID}:file-system/${EFS_ID}"

export EFS_BACKUP_JOB_ID="$(
  aws backup start-backup-job \
    --backup-vault-name "${BACKUP_VAULT_NAME}" \
    --resource-arn "${EFS_ARN}" \
    --iam-role-arn "${BACKUP_ROLE_ARN}" \
    --idempotency-token "${PROJECT_NAME}-${ENVIRONMENT}-${STAMP}" \
    --query BackupJobId \
    --output text
)"

aws backup describe-backup-job --backup-job-id "${EFS_BACKUP_JOB_ID}"

while true; do
  STATUS="$(aws backup describe-backup-job --backup-job-id "${EFS_BACKUP_JOB_ID}" --query State --output text)"
  echo "EFS backup status: ${STATUS}"
  case "${STATUS}" in
    COMPLETED) break ;;
    ABORTED|FAILED|EXPIRED) exit 1 ;;
  esac
  sleep 30
done
```

Do not continue a server upgrade until both the RDS snapshot and EFS backup are complete.

Record backup metadata in the incident/change ticket:

```text
Environment:
Target Moodle tag:
Previous ECS task definition:
RDS snapshot:
EFS backup vault:
EFS backup job:
Git commit/image tag:
Operator:
Timestamp:
```

### 4. Enable Server Maintenance Mode

Find a running Moodle task:

```bash
export TASK_ARN="$(
  aws ecs list-tasks \
    --cluster "${CLUSTER}" \
    --service-name "${SERVICE}" \
    --desired-status RUNNING \
    --query 'taskArns[0]' \
    --output text
)"
```

Enable maintenance mode:

```bash
aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/maintenance.php --enable'
```

Pause cron during the upgrade:

```bash
aws ecs update-service \
  --cluster "${CLUSTER}" \
  --service "${CRON_SERVICE}" \
  --desired-count 0
```

### 5. Build And Deploy Server Image

Preferred manual pipeline path:

1. Open GitHub Actions.
2. Select `Moodle Version Upgrade`.
3. Click `Run workflow`.
4. Select `target_environment`: `dev`, `stage`, or `prod`.
5. Enter the target official Moodle tag, for example `v5.2.1`.
6. Keep `backup_before_upgrade=true` for shared environments.
7. Keep `run_cli_upgrade=true` unless you intentionally want image deployment only.
8. Supply `backup_vault_name` and `backup_role_arn`, or configure repository variables.
9. Type the exact confirmation phrase, for example:

   ```text
   UPGRADE prod v5.2.1
   ```

The workflow validates the Moodle Git tag, creates RDS and EFS backups, builds and pushes the production image, applies Terraform with cron stopped, runs `admin/cli/upgrade.php`, purges caches, disables maintenance mode, and applies Terraform again with cron running.

The workflow summary records the previous ECS task definition, RDS snapshot ID, EFS backup job ID, Moodle tag, and image tag. Save that summary with the change ticket.

The workflow input does not permanently change the repository default Moodle version. After a successful upgrade, open a normal PR to update the default references when you want future CI builds to use the new tag by default:

```text
.github/workflows/ci_cd_pipeline.yml  env.MOODLE_VERSION
.env.example                          MOODLE_VERSION
docs/runbook.md                       version references when needed
docs/update.md                        version references when needed
```

Manual fallback path:

Open a PR and let CI pass. After merge, run the GitHub Actions `Moodle Delivery Pipeline` workflow for the target environment:

```text
publish_image=true
apply_infrastructure=true
target_environment=dev|stage|prod
```

The workflow builds the production image with `INCLUDE_MOODLE_SOURCE=true`, bakes the official Moodle tag into the image, pushes it to GHCR, and applies Terraform with the new image tag.

Wait for ECS deployment:

```bash
aws ecs wait services-stable \
  --cluster "${CLUSTER}" \
  --services "${SERVICE}"
```

### 6. Run Server Moodle CLI Upgrade

Refresh the running task ARN after deployment:

```bash
export TASK_ARN="$(
  aws ecs list-tasks \
    --cluster "${CLUSTER}" \
    --service-name "${SERVICE}" \
    --desired-status RUNNING \
    --query 'taskArns[0]' \
    --output text
)"
```

Run upgrade and purge caches:

```bash
aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/upgrade.php --non-interactive'

aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/purge_caches.php'
```

Restart cron:

```bash
aws ecs update-service \
  --cluster "${CLUSTER}" \
  --service "${CRON_SERVICE}" \
  --desired-count 1
```

Disable maintenance mode:

```bash
aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/maintenance.php --disable'
```

### 7. Server Verification

```bash
aws ecs describe-services \
  --cluster "${CLUSTER}" \
  --services "${SERVICE}" "${CRON_SERVICE}" \
  --query 'services[].{name:serviceName,status:status,desired:desiredCount,running:runningCount}'

aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/cfg.php --name=release'

aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/checks.php'

aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/check_database_schema.php'
```

Check the public URL from Terraform output:

```bash
curl -fsS "$(terraform -chdir="terraform/envs/${ENVIRONMENT}" output -raw moodle_wwwroot)" >/tmp/moodle-server-home.html
```

### 8. Server Rollback

Preferred manual pipeline path for application rollback:

1. Open GitHub Actions.
2. Select `Server Restore`.
3. Click `Run workflow`.
4. Select the target environment.
5. Enter the previous ECS task definition ARN when rolling back the application image.
6. Enter the RDS snapshot ID and EFS recovery point ARN when they should be validated.
7. Type the exact confirmation phrase, for example:

   ```text
   RESTORE prod
   ```

8. Run the workflow and keep the summary with the change ticket.

The restore workflow can pause cron, put Moodle in maintenance mode, validate the recorded RDS/EFS restore points, roll the ECS service back to a supplied task definition, purge caches, disable maintenance mode, and restart cron.

It does not automatically replace production RDS or EFS resources. RDS snapshot restore creates a new DB instance, and EFS recovery creates/restores file-system data through AWS Backup. Both require an explicit AWS/Terraform cutover plan.

If the new ECS deployment fails before `admin/cli/upgrade.php`, roll back only the application image:

```bash
aws ecs update-service \
  --cluster "${CLUSTER}" \
  --service "${SERVICE}" \
  --task-definition "<previous-task-definition-arn>"

aws ecs wait services-stable \
  --cluster "${CLUSTER}" \
  --services "${SERVICE}"
```

If `admin/cli/upgrade.php` has already modified the database, application-only rollback is not enough. Restore both:

1. RDS database from the recorded snapshot or point-in-time restore.
2. EFS `moodledata` from the recorded AWS Backup recovery point.
3. ECS service back to the matching previous task definition/image.
4. Moodle caches after the restored task is running.

RDS snapshot restore creates a new RDS instance; it does not overwrite the existing instance in place. Plan the cutover in AWS with the platform owner before production rollback. The current Terraform module owns the RDS instance directly, so production rollback should be handled as a controlled infrastructure change, not an ad hoc shell command.

EFS restore is handled through AWS Backup recovery points. Restore metadata depends on the backup vault and restore target, so use the approved AWS Backup restore procedure for the account.

After rollback, verify:

```bash
aws ecs wait services-stable \
  --cluster "${CLUSTER}" \
  --services "${SERVICE}" "${CRON_SERVICE}"

aws ecs execute-command \
  --cluster "${CLUSTER}" \
  --task "${TASK_ARN}" \
  --container moodle \
  --interactive \
  --command 'php admin/cli/purge_caches.php'
```

## Post-Event Checklist

- Confirm Moodle release and health checks.
- Confirm cron is running.
- Confirm login, dashboard, course listing, course view, and admin pages.
- Confirm file uploads work.
- Confirm email path for the target environment.
- Store backup IDs, image tag, Git commit, and verification notes in the change ticket.
- Keep rollback backups until the agreed retention period ends.
