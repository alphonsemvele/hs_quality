resource "aws_elasticache_subnet_group" "main" {
  name       = "${local.name_prefix}-redis-subnets"
  subnet_ids = aws_subnet.database[*].id

  tags = { Name = "${local.name_prefix}-redis-subnets" }
}

resource "aws_elasticache_parameter_group" "redis7" {
  name   = "${local.name_prefix}-redis7"
  family = "redis7"

  parameter {
    name  = "maxmemory-policy"
    value = "allkeys-lru"
  }
}

resource "aws_kms_key" "redis" {
  description             = "KMS key for ElastiCache encryption (${var.environment})"
  deletion_window_in_days = 10
  enable_key_rotation     = true

  tags = { Name = "${local.name_prefix}-redis-kms" }
}

resource "aws_kms_alias" "redis" {
  name          = "alias/${local.name_prefix}-redis"
  target_key_id = aws_kms_key.redis.key_id
}

resource "aws_elasticache_replication_group" "main" {
  replication_group_id       = "${local.name_prefix}-redis"
  description                = "Redis for ${local.name_prefix} (cache, queues, Reverb pub/sub)"
  engine                     = "redis"
  engine_version             = var.redis_engine_version
  node_type                  = var.redis_node_type
  num_cache_clusters         = var.redis_num_cache_clusters
  port                       = 6379
  parameter_group_name       = aws_elasticache_parameter_group.redis7.name
  subnet_group_name          = aws_elasticache_subnet_group.main.name
  security_group_ids         = [aws_security_group.database.id]
  automatic_failover_enabled = var.redis_num_cache_clusters > 1
  multi_az_enabled           = var.redis_num_cache_clusters > 1

  at_rest_encryption_enabled = true
  kms_key_id                 = aws_kms_key.redis.arn
  transit_encryption_enabled = true
  auth_token                 = random_password.redis_auth.result

  snapshot_retention_limit = 7
  snapshot_window          = "03:00-04:00"
  maintenance_window       = "sun:05:00-sun:06:00"

  apply_immediately = var.environment != "production"

  tags = { Name = "${local.name_prefix}-redis" }
}

resource "random_password" "redis_auth" {
  length      = 32
  special     = false  # ElastiCache AUTH token has limited special-char support
  min_lower   = 8
  min_upper   = 8
  min_numeric = 8
}
