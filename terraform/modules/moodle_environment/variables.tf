variable "project_name" {
  type        = string
  description = "Project name used as an AWS resource prefix."
}

variable "environment" {
  type        = string
  description = "Environment name, for example dev, stage, or prod."
}

variable "aws_region" {
  type        = string
  description = "AWS region."
}

variable "vpc_cidr" {
  type        = string
  description = "CIDR block for the environment VPC."
}

variable "public_subnet_cidrs" {
  type        = list(string)
  description = "CIDR blocks for public ALB subnets."
}

variable "private_subnet_cidrs" {
  type        = list(string)
  description = "CIDR blocks for private ECS, RDS, Redis, and EFS subnets."
}

variable "allowed_cidr_blocks" {
  type        = list(string)
  description = "CIDR blocks allowed to reach the public load balancer."
  default     = ["0.0.0.0/0"]
}

variable "certificate_arn" {
  type        = string
  description = "Optional ACM certificate ARN. When set, an HTTPS listener is created."
  default     = ""
}

variable "moodle_wwwroot" {
  type        = string
  description = "Public Moodle URL. Leave empty to use the ALB DNS name with HTTP."
  default     = ""
}

variable "image_tag" {
  type        = string
  description = "Container image tag to deploy."
}

variable "container_repository_url" {
  type        = string
  description = "Container image repository URL without tag."
}

variable "container_registry_credentials_secret_arn" {
  type        = string
  description = "Optional Secrets Manager secret ARN with private registry credentials for ECS repositoryCredentials."
  default     = ""
}

variable "desired_count" {
  type        = number
  description = "Desired ECS task count."
}

variable "cron_desired_count" {
  type        = number
  description = "Desired Moodle cron ECS task count."
  default     = 1
}

variable "task_cpu" {
  type        = number
  description = "Fargate task CPU units."
}

variable "task_memory" {
  type        = number
  description = "Fargate task memory in MiB."
}

variable "database_instance_class" {
  type        = string
  description = "RDS PostgreSQL instance class."
}

variable "database_allocated_storage" {
  type        = number
  description = "Initial RDS storage in GiB."
}

variable "database_max_allocated_storage" {
  type        = number
  description = "RDS autoscaling storage ceiling in GiB."
}

variable "database_backup_retention_days" {
  type        = number
  description = "RDS backup retention period."
}

variable "database_deletion_protection" {
  type        = bool
  description = "Whether to enable RDS deletion protection."
}

variable "redis_node_type" {
  type        = string
  description = "ElastiCache Redis node type."
}

variable "redis_node_count" {
  type        = number
  description = "Number of Redis cache nodes."
}

variable "enable_container_insights" {
  type        = bool
  description = "Enable ECS container insights."
  default     = true
}

variable "log_retention_days" {
  type        = number
  description = "CloudWatch log retention in days."
  default     = 30
}
