# CI/CD Pipeline

The GitHub Actions workflow is defined in [.github/workflows/ci_cd_pipeline.yml](.github/workflows/ci_cd_pipeline.yml).

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
| `publish-image` | Builds the production image with Moodle source and `moodle-overrides/` baked in, then pushes it to ECR. |
| `terraform-apply-dev` | Applies dev infrastructure. |
| `terraform-apply-stage` | Applies stage infrastructure. |
| `terraform-apply-prod` | Applies prod infrastructure. |

## Triggers

- Every branch push.
- Pull requests.
- Manual `workflow_dispatch`.

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

The image repository is ECR:

```text
<aws-account-id>.dkr.ecr.<region>.amazonaws.com/elearn-mindset
```

The workflow publishes automatically on `main`. Manual workflow runs can also publish by setting `publish_image` to `true`.

## Required GitHub Variables

Set these repository variables:

```text
AWS_ACCOUNT_ID=<your AWS account id>
AWS_REGION=us-west-2
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
