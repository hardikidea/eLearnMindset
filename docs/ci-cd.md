# CI/CD Pipeline

The GitHub Actions workflow is defined in [.github/workflows/ci_cd_pipeline.yml](.github/workflows/ci_cd_pipeline.yml).
Manual server operation workflows are defined in:

- [.github/workflows/server_backup.yml](.github/workflows/server_backup.yml)
- [.github/workflows/server_restore.yml](.github/workflows/server_restore.yml)
- [.github/workflows/moodle_version_upgrade.yml](.github/workflows/moodle_version_upgrade.yml)

The structure follows the CourseCloud reference at a high level: separate validation, image build, Terraform plan/apply, and deployment-oriented jobs. This project does not use the CourseCloud SSH role lookup; it uses GitHub OIDC directly.

## Jobs

| Job | Purpose |
| --- | --- |
| `validate` | Checks shell syntax, validates Docker Compose, bootstraps the configured Moodle tag, and confirms the checkout. |
| `lint` | Validates Renovate config, runs ShellCheck, YAML lint, Hadolint, Terraform formatting, and Terraform no-backend validation. |
| `security-audit` | Runs Composer audit for Moodle production PHP dependencies and Trivy filesystem/IaC scanning for high and critical findings. |
| `local-image-build` | Builds the local Docker image and the production image shape. |
| `integration-smoke` | Builds the local stack, installs Moodle with PostgreSQL, checks the home/login pages, and confirms the installed release. |
| `terraform-plan` | Plans dev, stage, and prod through environment-specific AWS OIDC roles. |
| `publish-image` | Builds the production image with Moodle source and `moodle-overrides/` baked in, then pushes it to GHCR. |
| `terraform-apply-dev` | Applies dev infrastructure. |
| `terraform-apply-stage` | Applies stage infrastructure. |
| `terraform-apply-prod` | Applies prod infrastructure. |

## Triggers

- Every branch push.
- Pull requests.
- Manual `workflow_dispatch`.

## Manual Server Workflows

Use these workflows from GitHub Actions for upgrade events and incident response.

| Workflow | Purpose |
| --- | --- |
| `Moodle Version Upgrade` | Manually validates an official Moodle tag, backs up the target environment, builds and deploys the new image, runs Moodle CLI upgrade, and restarts cron. |
| `Server Backup` | Manually creates an RDS DB snapshot and an EFS AWS Backup recovery point for `dev`, `stage`, or `prod`. |
| `Server Restore` | Manually validates restore-point IDs, pauses cron, optionally rolls ECS back to a previous task definition, purges caches, and restarts cron. |

`Server Restore` does not delete or replace Terraform-owned RDS/EFS resources automatically. Database and file restore still require the controlled AWS restore and Terraform cutover process in [docs/upgrade-backup-restore.md](upgrade-backup-restore.md).

Required backup variables:

```text
BACKUP_VAULT_NAME=<aws-backup-vault-name>
BACKUP_ROLE_ARN=<aws-backup-service-role-arn>
```

These can be set as repository variables or supplied as manual workflow inputs.

The restore workflow requires a typed confirmation:

```text
RESTORE dev
RESTORE stage
RESTORE prod
```

The Moodle upgrade workflow requires this confirmation format:

```text
UPGRADE dev v5.2.1
UPGRADE stage v5.2.1
UPGRADE prod v5.2.1
```

It deploys the selected Moodle tag as an image tagged `moodle-upgrade-<run-id>-<run-attempt>`, applies Terraform with cron desired count `0`, runs `php admin/cli/upgrade.php --non-interactive` through ECS Exec, then applies Terraform again with cron desired count `1`.

## Quality Gates

The deployment path is gated by linting and vulnerability checks:

- `renovate-config-validator --strict` validates [renovate.json](renovate.json).
- ShellCheck validates all project shell scripts under `scripts/` and `docker/moodle/`.
- Yamllint validates GitHub workflow YAML and Docker Compose YAML.
- Hadolint validates [docker/moodle/Dockerfile](docker/moodle/Dockerfile) using [.hadolint.yaml](.hadolint.yaml).
- Terraform runs `terraform fmt -check -recursive terraform`.
- Terraform validates `terraform/bootstrap` and each `terraform/envs/*` folder with `-backend=false`, so AWS credentials are not needed for static validation.
- Composer audit checks Moodle production PHP dependencies from `moodle/composer.lock`.
- Trivy scans the repository filesystem, Terraform/IaC, and the production Docker image for high and critical vulnerabilities.

The `local-image-build` job requires `validate`, `lint`, and `security-audit` to pass before Docker images are built. The publish and apply jobs depend on the downstream smoke-tested image path. `bootstrap-moodle.sh` syncs `moodle-overrides/` for local CI checks, and the production Dockerfile copies the same overrides after cloning the configured Moodle tag.

## Renovate

Renovate is configured in [renovate.json](renovate.json) and runs from [.github/workflows/renovate.yml](.github/workflows/renovate.yml).

The schedule is weekdays at `00:30 UTC`, with manual `workflow_dispatch` available. Renovate manages:

- GitHub Actions.
- Docker images in Dockerfile and Docker Compose.
- Terraform providers and modules.
- The configured Moodle release tag in `.env.example`, the Dockerfile, and CI.
- `PHPREDIS_VERSION`, `TRIVY_VERSION`, and `RENOVATE_CLI_VERSION`.

The Dependency Dashboard is enabled. Major updates require dashboard approval, while vulnerability remediation PRs are labeled `security` and `dependencies`.

Create a repository secret named `RENOVATE_TOKEN` for the best result. A fine-grained token or GitHub App token should have repository contents read/write, pull request read/write, issue read/write, workflow file write, action read, and security alert read permissions. The workflow falls back to `GITHUB_TOKEN`, but PRs created with `GITHUB_TOKEN` may not trigger all downstream workflow events.

## AWS OIDC

The workflow assumes these AWS roles, created by [terraform/bootstrap](terraform/bootstrap):

```text
elearn-mindset-dev-github-actions
elearn-mindset-stage-github-actions
elearn-mindset-prod-github-actions
```

No SSH key is required. The workflow needs GitHub `id-token: write` permission, which is already configured.

## Image Publishing

The image repository is GitHub Container Registry:

```text
ghcr.io/hardikidea/elearnmindset
```

The workflow publishes immutable deployment tags automatically on `main`. Manual workflow runs can also publish by setting `publish_image` to `true`.

The default image repository can be overridden with this repository variable:

```text
GHCR_IMAGE_NAME=ghcr.io/hardikidea/elearnmindset
```

If the GHCR package is private, ECS needs a Secrets Manager secret for registry pull credentials. The secret value must be JSON:

```json
{"username":"hardikidea","password":"<github-token-with-read-packages>"}
```

Set the resulting secret ARN in the target Terraform environment:

```hcl
container_registry_credentials_secret_arn = "arn:aws:secretsmanager:us-west-2:123456789012:secret:elearn-mindset/prod/ghcr-xxxxxx"
```

## Required GitHub Variables

Set these repository variables:

```text
AWS_ACCOUNT_ID=<your AWS account id>
AWS_REGION=us-west-2
GHCR_IMAGE_NAME=ghcr.io/hardikidea/elearnmindset
BACKUP_VAULT_NAME=<aws-backup-vault-name>
BACKUP_ROLE_ARN=<aws-backup-service-role-arn>
```

Set this repository secret for Renovate:

```text
RENOVATE_TOKEN=<github app token or fine-grained token>
```

Create GitHub Environments named `dev`, `stage`, and `prod`. Use environment approval rules for stage and prod.

## Local Equivalent

Run the closest local equivalent before pushing:

```bash
cp .env.example .env
./scripts/bootstrap-moodle.sh
docker compose config --quiet
npx --yes --package renovate@43.243.0 -- renovate-config-validator --strict
docker compose build
./scripts/install-site.sh
curl -fsS http://localhost:8080/ >/tmp/moodle-home.html
docker compose exec moodle php admin/cli/cfg.php --name=release
```

Read [terraform/README.md](terraform/README.md) for bootstrap and environment commands.

## References

- Renovate config validation: https://docs.renovatebot.com/config-validation/
- Renovate GitHub Action: https://github.com/renovatebot/github-action
- Renovate Dependency Dashboard: https://docs.renovatebot.com/key-concepts/dashboard/
- Trivy GitHub Action: https://github.com/aquasecurity/trivy-action
