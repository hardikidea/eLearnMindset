resource "aws_cloudwatch_log_group" "moodle" {
  name              = "/ecs/${local.name_prefix}"
  retention_in_days = var.log_retention_days

  tags = local.common_tags
}

resource "aws_ecs_cluster" "moodle" {
  name = "${local.name_prefix}-cluster"

  setting {
    name  = "containerInsights"
    value = var.enable_container_insights ? "enabled" : "disabled"
  }

  tags = merge(local.common_tags, {
    Name = "${local.name_prefix}-cluster"
  })
}

resource "aws_ecs_task_definition" "moodle" {
  family                   = "${local.name_prefix}-task"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = var.task_cpu
  memory                   = var.task_memory
  execution_role_arn       = aws_iam_role.task_execution.arn
  task_role_arn            = aws_iam_role.task.arn

  runtime_platform {
    cpu_architecture        = "X86_64"
    operating_system_family = "LINUX"
  }

  volume {
    name = "moodledata"

    efs_volume_configuration {
      file_system_id     = aws_efs_file_system.moodledata.id
      transit_encryption = "ENABLED"

      authorization_config {
        access_point_id = aws_efs_access_point.moodledata.id
        iam             = "ENABLED"
      }
    }
  }

  container_definitions = jsonencode([
    {
      name      = local.container_name
      image     = "${var.ecr_repository_url}:${var.image_tag}"
      essential = true

      portMappings = [
        {
          containerPort = local.container_port
          hostPort      = local.container_port
          protocol      = "tcp"
        }
      ]

      mountPoints = [
        {
          sourceVolume  = "moodledata"
          containerPath = "/var/www/moodledata"
          readOnly      = false
        }
      ]

      environment = local.moodle_environment
      secrets     = local.moodle_secrets

      linuxParameters = {
        initProcessEnabled = true
      }

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.moodle.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "moodle"
        }
      }
    }
  ])

  depends_on = [
    aws_efs_mount_target.moodledata
  ]

  tags = local.common_tags
}

resource "aws_ecs_task_definition" "cron" {
  family                   = "${local.name_prefix}-cron"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = 512
  memory                   = 1024
  execution_role_arn       = aws_iam_role.task_execution.arn
  task_role_arn            = aws_iam_role.task.arn

  runtime_platform {
    cpu_architecture        = "X86_64"
    operating_system_family = "LINUX"
  }

  volume {
    name = "moodledata"

    efs_volume_configuration {
      file_system_id     = aws_efs_file_system.moodledata.id
      transit_encryption = "ENABLED"

      authorization_config {
        access_point_id = aws_efs_access_point.moodledata.id
        iam             = "ENABLED"
      }
    }
  }

  container_definitions = jsonencode([
    {
      name      = "cron"
      image     = "${var.ecr_repository_url}:${var.image_tag}"
      essential = true
      command   = ["moodle-cron-loop"]

      mountPoints = [
        {
          sourceVolume  = "moodledata"
          containerPath = "/var/www/moodledata"
          readOnly      = false
        }
      ]

      environment = local.moodle_environment
      secrets     = local.moodle_secrets

      linuxParameters = {
        initProcessEnabled = true
      }

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.moodle.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "cron"
        }
      }
    }
  ])

  depends_on = [
    aws_efs_mount_target.moodledata
  ]

  tags = local.common_tags
}

resource "aws_ecs_service" "moodle" {
  name                   = "${local.name_prefix}-service"
  cluster                = aws_ecs_cluster.moodle.id
  task_definition        = aws_ecs_task_definition.moodle.arn
  desired_count          = var.desired_count
  launch_type            = "FARGATE"
  enable_execute_command = true

  network_configuration {
    subnets          = values(aws_subnet.private)[*].id
    security_groups  = [aws_security_group.ecs.id]
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.moodle.arn
    container_name   = local.container_name
    container_port   = local.container_port
  }

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  depends_on = [
    aws_lb_listener.http
  ]

  tags = local.common_tags
}

resource "aws_ecs_service" "cron" {
  name                   = "${local.name_prefix}-cron"
  cluster                = aws_ecs_cluster.moodle.id
  task_definition        = aws_ecs_task_definition.cron.arn
  desired_count          = var.cron_desired_count
  launch_type            = "FARGATE"
  enable_execute_command = true

  network_configuration {
    subnets          = values(aws_subnet.private)[*].id
    security_groups  = [aws_security_group.ecs.id]
    assign_public_ip = false
  }

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  tags = local.common_tags
}

resource "aws_appautoscaling_target" "moodle" {
  max_capacity       = max(var.desired_count * 3, 2)
  min_capacity       = var.desired_count
  resource_id        = "service/${aws_ecs_cluster.moodle.name}/${aws_ecs_service.moodle.name}"
  scalable_dimension = "ecs:service:DesiredCount"
  service_namespace  = "ecs"
}

resource "aws_appautoscaling_policy" "cpu" {
  name               = "${local.name_prefix}-cpu"
  policy_type        = "TargetTrackingScaling"
  resource_id        = aws_appautoscaling_target.moodle.resource_id
  scalable_dimension = aws_appautoscaling_target.moodle.scalable_dimension
  service_namespace  = aws_appautoscaling_target.moodle.service_namespace

  target_tracking_scaling_policy_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ECSServiceAverageCPUUtilization"
    }

    target_value = 70
  }
}
