# eLearn Mindset Project Runbook

This runbook is the operator entry point for the eLearn Mindset local Moodle stack, CI/CD pipeline, Renovate automation, and AWS Terraform environments.

Use this with the focused docs:

- [Local setup](setup.md)
- [Docker architecture](docker.md)
- [CI/CD pipeline](ci-cd.md)
- [Theme setup](theme.md)
- [Moodle update process](update.md)
- [Terraform infrastructure](../terraform/README.md)

## System Overview

| Area | Value |
| --- | --- |
| Project root | Repository root |
| Moodle source | `moodle/`, cloned from `https://github.com/moodle/moodle.git` |
| Moodle version | `v5.2.1` by default |
| Moodle local URL | `http://localhost:8080` |
| PostgreSQL host port | `127.0.0.1:5440` |
| PostgreSQL container address | `db:5432` |
| MailPit UI | `http://localhost:8025` |
| MailPit SMTP from Moodle | `mailpit:1025` |
| MailPit SMTP from host | `127.0.0.1:1025` |
| Redis host port | `127.0.0.1:6379` |
| Docker Compose project | `elearn_mindset` |
| Terraform environments | `dev`, `stage`, `prod` |
| AWS authentication | GitHub OIDC, no SSH keys |
| Active theme | `almondb`, with eLearn Mindset palette overrides |

## Local Services

| Service | Container | Purpose |
| --- | --- | --- |
| `moodle` | `elearn_mindset_app` | PHP 8.3, PHP-FPM, Nginx web runtime |
| `cron` | `elearn_mindset_cron` | Runs Moodle cron every `MOODLE_CRON_INTERVAL` seconds |
| `db` | `elearn_mindset_db` | PostgreSQL 16 |
| `redis` | `elearn_mindset_redis` | Optional cache backend |
| `mailpit` | `elearn_mindset_mailpit` | Local SMTP catcher |

## Required Local Tools

- Docker Desktop or Docker Engine with Compose v2.
- Git.
- Terraform for local infrastructure validation and plans.
- Node.js only when running the Renovate config validator locally without Docker.
- At least 5 GB free disk space.

## First Time Local Setup

Run from the project root:

```bash
cp .env.example .env
./scripts/bootstrap-moodle.sh
docker compose build
docker compose up -d
./scripts/install-site.sh
make demo-data
```

Open Moodle:

```text
http://localhost:8080
```

Open MailPit:

```text
http://localhost:8025
```

Default local admin credentials come from `.env`:

```text
MOODLE_ADMIN_USER=admin
MOODLE_ADMIN_PASSWORD=Admin123!ChangeMe
```

Change the admin password before sharing this environment with anyone.

## Indian School Demo Data Runbook

Seed the eLearn Mindset demo package:

```bash
make demo-data
```

The seed command creates:

- Primary School and Higher Secondary School categories.
- Class 1, Class 3, Class 11 Science, and Class 11 Commerce course shells.
- Activity-based Primary topics and board/entrance-focused Higher Secondary topics.
- Principal, IT Coordinator, PRT, PGT, and student accounts.
- Manual course enrolments and system Manager roles for `principal_sharma` and `it_coord_nair`.

The seed command is idempotent. Rerunning it updates existing users, courses, sections, and categories and skips existing activity modules by idnumber.

Demo package files:

```text
moodle/demo-data/indian-school/users.csv
moodle/demo-data/indian-school/categories.csv
moodle/demo-data/indian-school/course-activity-blueprint.md
```

Seeded demo user password:

```text
SchoolDemo2026!
```

## Start, Stop, And Status

```bash
make start                     # start all services
make stop                      # stop all services, keep volumes/data
make restart                   # restart Moodle web and cron
make status                    # show container status
docker compose down -v         # stop services and delete Docker volumes
```

Equivalent Docker commands:

```bash
docker compose up -d
docker compose down
docker compose restart moodle cron
docker compose ps
```

## Health Checks

Check containers:

```bash
docker compose ps
```

Check Moodle homepage:

```bash
curl -fsS --max-time 30 http://localhost:8080/ >/tmp/moodle-home.html
grep -q "eLearn Mindset Local" /tmp/moodle-home.html
```

Check login page:

```bash
curl -fsS --max-time 30 http://localhost:8080/login/index.php >/tmp/moodle-login.html
```

Check installed Moodle release:

```bash
docker compose exec moodle php admin/cli/cfg.php --name=release
```

Check PostgreSQL readiness:

```bash
docker compose exec db pg_isready -U moodle -d moodle
```

Check MailPit UI:

```bash
curl -fsS http://localhost:8025 >/tmp/mailpit.html
```

## Daily Operations

View Moodle logs:

```bash
docker compose logs -f moodle
```

Open a Moodle container shell:

```bash
docker compose exec moodle bash
```

Open PostgreSQL:

```bash
docker compose exec db psql -U moodle -d moodle
```

Run Moodle cron once:

```bash
make cron
```

Purge Moodle caches:

```bash
docker compose exec moodle php admin/cli/purge_caches.php
```

Install or re-apply the project theme:

```bash
make theme-install
```

Enable maintenance mode:

```bash
docker compose exec moodle php admin/cli/maintenance.php --enable
```

Disable maintenance mode:

```bash
docker compose exec moodle php admin/cli/maintenance.php --disable
```

## MailPit Runbook

MailPit is used for local email testing only.

Current values:

```text
MOODLE_SMTP_HOSTS=mailpit:1025
MOODLE_NOREPLY_ADDRESS=noreply@example.local
MAILPIT_SMTP_PORT=1025
MAILPIT_UI_PORT=8025
```

Apply or re-apply Moodle SMTP settings:

```bash
make configure-mailpit
```

Verify Moodle SMTP config:

```bash
docker compose exec moodle php admin/cli/cfg.php --name=smtphosts
docker compose exec moodle php admin/cli/cfg.php --name=noreplyaddress
```

Send a direct SMTP test message to MailPit from the host:

```bash
cat >/tmp/mailpit-test.txt <<'EOF'
From: Moodle Local <noreply@example.local>
To: Admin <admin@example.local>
Subject: MailPit configuration test

MailPit SMTP is reachable for the Moodle local Docker project.
EOF

curl --url smtp://127.0.0.1:1025 \
  --mail-from noreply@example.local \
  --mail-rcpt admin@example.local \
  --upload-file /tmp/mailpit-test.txt
```

Confirm it appears in MailPit:

```bash
curl -fsS http://localhost:8025/api/v1/messages
```

## Backup Runbook

Create a local backup:

```bash
make backup
```

The script writes to:

```text
backups/<timestamp>/
```

Backup contents:

| File | Purpose |
| --- | --- |
| `postgres.sql` | Plain SQL database dump |
| `moodledata.tar.gz` | Moodle data directory archive |
| `moodle-version.txt` | Moodle Git tag and latest version commit |

Confirm latest backup:

```bash
ls -lt backups
```

## Local Restore Runbook

Use this only for a local Docker restore. It deletes the local PostgreSQL Docker volume.

Set the backup folder:

```bash
BACKUP_DIR=backups/20260625-202342
```

If the backup came from a different Moodle tag, inspect the recorded version and check out that tag before restoring:

```bash
cat "${BACKUP_DIR}/moodle-version.txt"
git -C moodle checkout <recorded-tag>
```

Restore database and `moodledata`:

```bash
docker compose down -v
rm -rf moodledata
tar -xzf "${BACKUP_DIR}/moodledata.tar.gz"
docker compose up -d db redis mailpit moodle

for attempt in {1..30}; do
  docker compose exec -T db pg_isready -U moodle -d moodle && break
  sleep 1
done

cat "${BACKUP_DIR}/postgres.sql" | docker compose exec -T db psql -U moodle -d moodle
docker compose exec -T moodle php admin/cli/purge_caches.php
./scripts/configure-mailpit.sh
docker compose up -d cron
```

Verify:

```bash
docker compose ps
curl -fsS http://localhost:8080/ >/tmp/moodle-home.html
```

## Fresh Reset Runbook

Use this when the local install should be recreated from scratch:

```bash
docker compose down -v
rm -rf moodle moodledata backups
./scripts/bootstrap-moodle.sh
docker compose build
docker compose up -d
./scripts/install-site.sh
```

## Moodle Update Runbook

This project follows Moodle Git administrator guidance: use official release tags, not `main`.

Check current version:

```bash
git -C moodle describe --tags --always --dirty
git -C moodle log --oneline -1 public/version.php
```

Find available Moodle 5.2 tags:

```bash
git ls-remote --tags --refs https://github.com/moodle/moodle.git 'v5.2*'
```

Update to a patch release:

```bash
./scripts/update-moodle.sh v5.2.2
```

The update script:

1. Confirms the target tag exists upstream.
2. Creates a backup.
3. Enables Moodle maintenance mode when possible.
4. Fetches Moodle tags.
5. Checks out the target tag.
6. Updates `.env` with the new `MOODLE_VERSION`.
7. Runs Composer install.
8. Runs Moodle CLI upgrade.
9. Purges caches.
10. Disables maintenance mode.
11. Starts cron.

Post-update verification:

```bash
docker compose exec moodle php admin/cli/cfg.php --name=release
docker compose exec moodle php admin/cli/cron.php
curl -fsS http://localhost:8080/ >/tmp/moodle-home.html
```

## Local Quality Gate Runbook

Run these before pushing:

```bash
docker compose config --quiet
bash -n scripts/*.sh docker/moodle/*.sh
npx --yes --package renovate@43.243.0 -- renovate-config-validator --strict
terraform fmt -check -recursive terraform
```

Run ShellCheck with Docker:

```bash
docker run --rm \
  -v "$PWD:/mnt" \
  -w /mnt \
  koalaman/shellcheck:stable \
  scripts/*.sh docker/moodle/*.sh
```

Run YAML lint with Docker:

```bash
docker run --rm \
  -v "$PWD:/work" \
  -w /work \
  cytopia/yamllint:latest \
  .github/workflows docker-compose.yml
```

Run Hadolint with Docker:

```bash
docker run --rm \
  -v "$PWD:/work" \
  -w /work \
  hadolint/hadolint:v2.12.0 \
  hadolint -c .hadolint.yaml docker/moodle/Dockerfile
```

Run Terraform validation without a backend:

```bash
for dir in terraform/bootstrap terraform/envs/dev terraform/envs/stage terraform/envs/prod; do
  terraform -chdir="${dir}" init -backend=false -input=false
  terraform -chdir="${dir}" validate
done
```

Run local Composer audit:

```bash
composer audit --locked --no-dev --format=plain --working-dir=moodle
```

If local Composer has certificate issues, run the CI-equivalent container command:

```bash
docker run --rm \
  -v "$PWD/moodle:/app" \
  -w /app \
  composer:2 \
  composer audit --locked --no-dev --format=plain
```

## Current Security Audit Status

As of 2026-06-25, the current Moodle `composer.lock` reports upstream dependency advisories during `composer audit`, including a high-severity advisory for `aws/aws-sdk-php`.

The CI `security-audit` job is intentionally configured to fail when high or critical vulnerabilities are present. Resolve these by updating to a Moodle release or dependency set that includes fixed transitive packages, then rerun the audit.

## CI/CD Runbook

Main pipeline:

```text
.github/workflows/ci_cd_pipeline.yml
```

Renovate pipeline:

```text
.github/workflows/renovate.yml
```

Pipeline order:

1. `validate`
2. `lint`
3. `security-audit`
4. `local-image-build`
5. `integration-smoke`
6. `terraform-plan`
7. `publish-image`
8. `terraform-apply-dev`
9. `terraform-apply-stage`
10. `terraform-apply-prod`

Deployment is gated through linting, security audit, image build, and smoke tests.

Required GitHub repository variables:

```text
AWS_ACCOUNT_ID=<your AWS account id>
AWS_REGION=us-west-2
```

Required GitHub repository secret for Renovate:

```text
RENOVATE_TOKEN=<github app token or fine-grained token>
```

Required GitHub Environments:

```text
dev
stage
prod
```

Use approval rules for `stage` and `prod`.

## Renovate Runbook

Renovate config:

```text
renovate.json
```

Schedule:

```text
30 0 * * 1-5
```

That is weekdays at `00:30 UTC`.

Renovate manages:

- GitHub Actions.
- Docker images in `docker-compose.yml` and Dockerfiles.
- Terraform providers and modules.
- Moodle release tag constants.
- `PHPREDIS_VERSION`.
- `TRIVY_VERSION`.
- `RENOVATE_CLI_VERSION`.

Renovate behavior:

- Dependency Dashboard is enabled.
- Major updates require dashboard approval.
- Vulnerability remediation PRs are labeled `security` and `dependencies`.
- GitHub Actions digest pinning is enabled.

Manually run Renovate:

1. Open the GitHub repository.
2. Go to Actions.
3. Select `Renovate`.
4. Run workflow.

If Renovate PRs do not trigger CI, confirm `RENOVATE_TOKEN` is configured. PRs created with the default `GITHUB_TOKEN` may not trigger downstream workflow events.

## Terraform Runbook

Terraform layout:

| Path | Purpose |
| --- | --- |
| `terraform/bootstrap` | State bucket, lock table, ECR, GitHub OIDC provider, GitHub Actions roles |
| `terraform/envs/dev` | Dev environment |
| `terraform/envs/stage` | Stage environment |
| `terraform/envs/prod` | Prod environment |
| `terraform/modules/moodle_environment` | Reusable Moodle AWS stack |
| `terraform/modules/route53` | Route53 ALB alias records |

Bootstrap once:

```bash
cd terraform/bootstrap
cp terraform.tfvars.example terraform.tfvars
terraform init
terraform plan
terraform apply
```

The bootstrap layer creates OIDC roles:

```text
elearn-mindset-dev-github-actions
elearn-mindset-stage-github-actions
elearn-mindset-prod-github-actions
```

Initialize an environment locally:

```bash
export AWS_ACCOUNT_ID=123456789012
export AWS_REGION=us-west-2
export PROJECT_NAME=elearn-mindset

terraform -chdir=terraform/envs/dev init \
  -backend-config="bucket=${PROJECT_NAME}-${AWS_ACCOUNT_ID}-${AWS_REGION}-tfstate" \
  -backend-config="dynamodb_table=${PROJECT_NAME}-terraform-locks" \
  -backend-config="key=${PROJECT_NAME}/dev.tfstate" \
  -backend-config="region=${AWS_REGION}" \
  -backend-config="encrypt=true"
```

Plan dev:

```bash
terraform -chdir=terraform/envs/dev plan -var "image_tag=latest"
```

Replace `dev` with `stage` or `prod` for other environments.

## Route53 Runbook

DNS is disabled by default. Enable it in an environment `terraform.tfvars`:

```hcl
route53_zone_id     = "Z1234567890ABC"
route53_record_name = "moodle-dev.example.com"
certificate_arn     = "arn:aws:acm:us-west-2:123456789012:certificate/..."
```

Recommended records:

```text
dev:   moodle-dev.example.com
stage: moodle-stage.example.com
prod:  moodle.example.com
```

If `moodle_wwwroot` is empty, Terraform derives it from the Route53 record. When `certificate_arn` is set, Moodle uses `https://<record-name>`.

To create an IPv6 AAAA alias too:

```hcl
route53_create_ipv6_record = true
```

## AWS ECS First Install Runbook

Terraform provisions infrastructure and deploys the container image. The first Moodle database installation on ECS must be run once through ECS Exec. This uses AWS APIs, not SSH.

List tasks:

```bash
aws ecs list-tasks \
  --cluster elearn-mindset-dev-cluster \
  --service-name elearn-mindset-dev-service
```

Run install:

```bash
aws ecs execute-command \
  --cluster elearn-mindset-dev-cluster \
  --task <task-arn> \
  --container moodle \
  --interactive \
  --command "sh -lc 'php admin/cli/install_database.php --agree-license --adminuser=\"\$MOODLE_ADMIN_USER\" --adminpass=\"\$MOODLE_ADMIN_PASSWORD\" --adminemail=\"\$MOODLE_ADMIN_EMAIL\" --fullname=\"\$MOODLE_SITE_FULLNAME\" --shortname=\"\$MOODLE_SITE_SHORTNAME\"'"
```

Purge caches:

```bash
aws ecs execute-command \
  --cluster elearn-mindset-dev-cluster \
  --task <task-arn> \
  --container moodle \
  --interactive \
  --command 'php admin/cli/purge_caches.php'
```

Repeat with `stage` or `prod` names when installing those environments.

## Production Backup Notes

The local `scripts/backup.sh` is for the local Docker stack.

For AWS environments, use AWS-native backups:

- RDS automated backups and snapshots for PostgreSQL.
- EFS backup through AWS Backup or an equivalent EFS backup plan.
- ECR image tags for application image rollback.
- Terraform remote state in S3 with versioning.

Before production upgrades, confirm an RDS restore point and EFS backup exist.

## Incident Response

### Moodle Is Down Locally

1. Check containers:

   ```bash
   docker compose ps
   ```

2. Check logs:

   ```bash
   docker compose logs --no-color moodle
   docker compose logs --no-color db
   ```

3. Check database health:

   ```bash
   docker compose exec db pg_isready -U moodle -d moodle
   ```

4. Restart Moodle:

   ```bash
   docker compose restart moodle cron
   ```

### PostgreSQL Port Conflict

Default host port is `5440`. If it is already used, change this in `.env`:

```text
POSTGRES_PORT=5441
```

Then recreate the port mapping:

```bash
docker compose up -d db
```

### Moodle URL Or Redirect Issue

Confirm `.env` values:

```text
MOODLE_HTTP_PORT=8080
MOODLE_WWWROOT=http://localhost:8080
MOODLE_REVERSEPROXY=false
```

Restart Moodle:

```bash
docker compose restart moodle
docker compose exec moodle php admin/cli/purge_caches.php
```

### Mail Not Appearing In MailPit

1. Confirm MailPit is running:

   ```bash
   docker compose ps mailpit
   ```

2. Re-apply SMTP config:

   ```bash
   make configure-mailpit
   ```

3. Check Moodle config:

   ```bash
   docker compose exec moodle php admin/cli/cfg.php --name=smtphosts
   ```

4. Open:

   ```text
   http://localhost:8025
   ```

### CI Fails In Security Audit

1. Open the `security-audit` job logs.
2. Identify whether the failure is Composer or Trivy.
3. For Composer, run:

   ```bash
   composer audit --locked --no-dev --format=plain --working-dir=moodle
   ```

4. For Trivy, inspect the affected image, package, or Terraform finding.
5. Update the Moodle tag, Docker image tag, Terraform provider, or dependency source.
6. Rerun CI.

### CI Fails In Terraform Plan

Check:

- `AWS_ACCOUNT_ID` repository variable exists.
- `AWS_REGION` repository variable exists.
- GitHub environments `dev`, `stage`, and `prod` exist.
- Bootstrap was applied.
- OIDC roles exist in AWS.
- The Terraform backend bucket and DynamoDB lock table exist.

### Route53 Record Does Not Resolve

Check:

```bash
terraform -chdir=terraform/envs/dev output
```

Then verify:

- `route53_zone_id` is correct.
- `route53_record_name` is in the hosted zone.
- The ALB DNS name exists.
- The certificate is in the same AWS region as the ALB.
- DNS propagation has completed.

## Command Index

| Task | Command |
| --- | --- |
| Bootstrap Moodle source | `make bootstrap` |
| Build images | `make build` |
| Start services | `make start` |
| Stop services | `make stop` |
| Restart Moodle and cron | `make restart` |
| Show status | `make status` |
| Install Moodle database | `make install` |
| Configure MailPit | `make configure-mailpit` |
| Run Moodle cron once | `make cron` |
| Create backup | `make backup` |
| Update Moodle | `./scripts/update-moodle.sh v5.2.2` |
| Moodle shell | `make shell` |
| Moodle logs | `make logs` |
| PostgreSQL shell | `docker compose exec db psql -U moodle -d moodle` |
| Purge Moodle cache | `docker compose exec moodle php admin/cli/purge_caches.php` |
| Stop and delete volumes | `docker compose down -v` |

## Ownership Checklist

Before considering the project ready for shared use:

- Change local admin password from the default.
- Configure `RENOVATE_TOKEN`.
- Configure GitHub repository variables `AWS_ACCOUNT_ID` and `AWS_REGION`.
- Create GitHub environments `dev`, `stage`, and `prod`.
- Apply Terraform bootstrap.
- Configure Route53 values for each environment that needs DNS.
- Confirm RDS and EFS backup strategy for AWS environments.
- Resolve high and critical dependency audit findings.
