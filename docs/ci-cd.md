# Moodle Delivery Pipeline

The GitHub Actions workflow is defined in [.github/workflows/ci_cd_pipeline.yml](../.github/workflows/ci_cd_pipeline.yml).
Manual server operation workflows are defined in:

- [.github/workflows/server_backup.yml](../.github/workflows/server_backup.yml)
- [.github/workflows/server_restore.yml](../.github/workflows/server_restore.yml)
- [.github/workflows/moodle_version_upgrade.yml](../.github/workflows/moodle_version_upgrade.yml)
- [.github/workflows/infrastructure_drift_detection.yml](../.github/workflows/infrastructure_drift_detection.yml)

The structure follows the CourseCloud reference at a high level: separate quality gates, image packaging, Terraform plan/apply, service stabilization, and staged production approval. This project does not use the CourseCloud SSH role lookup; it uses GitHub OIDC directly.

## Jobs

| Job | Purpose |
| --- | --- |
| `change_eligibility` | Runs the graph only for pull requests, `main`, manual runs, or branches with an open PR. |
| `source_integrity` | Checks shell syntax, validates Docker Compose, bootstraps the configured Moodle tag, and confirms the checkout. |
| `static_quality` | Validates Renovate config, ShellCheck, YAML, Hadolint, Terraform formatting, and Terraform no-backend validation. |
| `documentation_quality` | Validates local Markdown links in README, docs, and Terraform documentation. |
| `supply_chain_security` | Runs Composer audit and Trivy filesystem/IaC scanning for high and critical findings. |
| `application_image_ci` | Builds the local Docker image, builds the production image shape, and scans the production image. |
| `worker_image_ci` | Builds the Moodle cron/worker-compatible image shape. The ECS cron service uses the same published image with a different command. |
| `local_integration_smoke` | Builds the local stack, installs Moodle with PostgreSQL, checks home/login pages, and confirms the installed release. |
| `release_metadata` | Produces immutable GHCR tags for PR, manual, and `main` runs. |
| `publish_release_candidate` | Builds the production image with Moodle source and `moodle-overrides/` baked in, then pushes it to GHCR. |
| `plan_dev_infrastructure`, `plan_stage_infrastructure`, `plan_prod_infrastructure` | Plans each environment through environment-specific AWS OIDC roles. |
| `apply_dev_infrastructure`, `apply_stage_infrastructure`, `apply_prod_infrastructure` | Applies the selected environment after the image is published and plans pass. |
| `stabilize_dev_web`, `stabilize_stage_web`, `stabilize_prod_web` | Waits for the Moodle ECS web service to stabilize. |
| `stabilize_dev_worker`, `stabilize_stage_worker`, `stabilize_prod_worker` | Waits for the Moodle ECS cron/worker service to stabilize. |
| `stage_smoke_validation` | Checks the stage Moodle home and login pages after stage deployment. |
| `stage_browser_validation` | Runs a Playwright suite from `e2e/` when the suite exists; otherwise records a clean no-op. |
| `production_change_approval` | Uses the `prod-approval` GitHub Environment as the production approval gate. |
| `production_smoke_validation` | Checks the production Moodle home and login pages after prod ECS stabilization. |
| `provision_behat_environment`, `behat_acceptance_tests`, `publish_behat_report`, `teardown_behat_environment` | Optional Behat integration-test hooks for manual runs. |
| `provision_phpunit_environment`, `phpunit_integration_tests`, `publish_phpunit_report`, `teardown_phpunit_environment` | Optional PHPUnit integration-test hooks for manual runs. |

## Composite Actions

Reusable workflow logic lives under `.github/actions/`:

| Action | Purpose |
| --- | --- |
| [change-eligibility](../.github/actions/change-eligibility/action.yml) | PR/main/manual run eligibility detection. |
| [moodle-source-integrity](../.github/actions/moodle-source-integrity/action.yml) | Moodle source bootstrap and baseline project validation. |
| [static-quality-gate](../.github/actions/static-quality-gate/action.yml) | Renovate, shell, YAML, Dockerfile, and Terraform static checks. |
| [documentation-quality-gate](../.github/actions/documentation-quality-gate/action.yml) | Local Markdown documentation link validation. |
| [supply-chain-security](../.github/actions/supply-chain-security/action.yml) | Composer audit plus Trivy repository/IaC scanning. |
| [moodle-image-build](../.github/actions/moodle-image-build/action.yml) | Reusable Moodle Docker image build and optional image scan. |
| [local-moodle-smoke](../.github/actions/local-moodle-smoke/action.yml) | Local Docker install and Moodle smoke test. |
| [release-image-metadata](../.github/actions/release-image-metadata/action.yml) | Immutable GHCR image tag calculation. |
| [ghcr-build-push](../.github/actions/ghcr-build-push/action.yml) | GHCR login and production image publish. |
| [terraform-run](../.github/actions/terraform-run/action.yml) | OIDC-backed Terraform plan/apply. |
| [ecs-service-wait](../.github/actions/ecs-service-wait/action.yml) | ECS web/worker service stabilization. |
| [remote-moodle-smoke](../.github/actions/remote-moodle-smoke/action.yml) | Remote Moodle smoke checks from Terraform outputs. |
| [playwright-e2e-if-present](../.github/actions/playwright-e2e-if-present/action.yml) | Conditional Playwright browser suite execution. |
| [integration-test-workspace](../.github/actions/integration-test-workspace/action.yml) | Extended-test workspace metadata. |
| [junit-placeholder](../.github/actions/junit-placeholder/action.yml) | Skipped JUnit artifact for future integration suites. |
| [terraform-drift-detect](../.github/actions/terraform-drift-detect/action.yml) | Refresh-only Terraform drift detection for AWS environments. |

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
| `Infrastructure Drift Detection` | Scheduled and manual Terraform refresh-only plans to detect out-of-band AWS changes. |

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

The drift workflow runs weekdays at `02:10 UTC` and supports manual `workflow_dispatch` for `dev`, `stage`, `prod`, or all environments. It fails by default when remote AWS resources differ from Terraform state.

## Quality Gates

The deployment path is gated by linting and vulnerability checks:

- `renovate-config-validator --strict` validates [renovate.json](../renovate.json).
- ShellCheck validates all project shell scripts under `scripts/` and `docker/moodle/`.
- Yamllint validates GitHub workflow YAML and Docker Compose YAML.
- Actionlint validates GitHub workflow syntax, expressions, and job dependencies.
- Hadolint validates [docker/moodle/Dockerfile](../docker/moodle/Dockerfile) using [.hadolint.yaml](../.hadolint.yaml).
- Terraform runs `terraform fmt -check -recursive terraform`.
- Terraform validates `terraform/bootstrap` and each `terraform/envs/*` folder with `-backend=false`, so AWS credentials are not needed for static validation.
- Composer audit checks Moodle production PHP dependencies from `moodle/composer.lock`.
- Trivy scans the repository filesystem, Terraform/IaC, and the production Docker image for high and critical vulnerabilities.

The `application_image_ci` and `worker_image_ci` jobs require `source_integrity`, `static_quality`, and `supply_chain_security` to pass before Docker images are built. The publish and apply jobs depend on the downstream smoke-tested image path. `bootstrap-moodle.sh` syncs `moodle-overrides/` for local CI checks, and the production Dockerfile copies the same overrides after cloning the configured Moodle tag.

ECS deployment circuit breaker, target group health checks, service stabilization waits, stage smoke validation, and production smoke validation are the pipeline rollback signals. If production smoke fails after a Moodle CLI schema upgrade, follow [upgrade, backup, and restore](upgrade-backup-restore.md); do not rely on image rollback alone.

## Renovate

Renovate is configured in [renovate.json](../renovate.json) and runs from [.github/workflows/renovate.yml](../.github/workflows/renovate.yml).

The schedule is weekdays at `00:30 UTC`, with manual `workflow_dispatch` available. Renovate manages:

- GitHub Actions.
- Docker images in Dockerfile and Docker Compose.
- Terraform providers and modules.
- The configured Moodle release tag in `.env.example`, the Dockerfile, and CI.
- `PHPREDIS_VERSION`, `TRIVY_VERSION`, and `RENOVATE_CLI_VERSION`.

The Dependency Dashboard is enabled. Major updates require dashboard approval, while vulnerability remediation PRs are labeled `security` and `dependencies`.

Create a repository secret named `RENOVATE_TOKEN` for the best result. A fine-grained token or GitHub App token should have repository contents read/write, pull request read/write, issue read/write, workflow file write, action read, and security alert read permissions. The workflow falls back to `GITHUB_TOKEN`, but PRs created with `GITHUB_TOKEN` may not trigger all downstream workflow events.

## AWS OIDC

The workflow assumes these AWS roles, created by [terraform/bootstrap](../terraform/bootstrap):

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

Create GitHub Environments named `dev`, `stage`, `prod`, and `prod-approval`. Use environment approval rules for stage, prod, and especially `prod-approval`.

## Manual CI/CD Inputs

Manual runs support:

| Input | Purpose |
| --- | --- |
| `publish_image` | Pushes the GHCR image. Set this to `true` before applying infrastructure. |
| `apply_infrastructure` | Allows Terraform apply jobs. Keep this `false` for plan-only manual validation. |
| `target_environment` | Limits apply jobs to `dev`, `stage`, `prod`, or all environments. |
| `run_extended_tests` | Enables optional Behat and PHPUnit integration-test hooks. These hooks currently publish skipped JUnit artifacts until dedicated test infrastructure is added. |

## Local Equivalent

Run the closest local equivalent before pushing:

```bash
cp .env.example .env
./scripts/bootstrap-moodle.sh
docker compose config --quiet
./scripts/validate-docs.sh
npx --yes --package renovate@43.243.0 -- renovate-config-validator --strict
docker run --rm -v "$PWD:/repo" -w /repo rhysd/actionlint:latest .github/workflows/*.yml
docker compose build
./scripts/install-site.sh
curl -fsS http://localhost:8080/ >/tmp/moodle-home.html
docker compose exec moodle php admin/cli/cfg.php --name=release
```

Read [terraform/README.md](../terraform/README.md) for bootstrap and environment commands.

## References

- Renovate config validation: https://docs.renovatebot.com/config-validation/
- Renovate GitHub Action: https://github.com/renovatebot/github-action
- Renovate Dependency Dashboard: https://docs.renovatebot.com/key-concepts/dashboard/
- Trivy GitHub Action: https://github.com/aquasecurity/trivy-action
