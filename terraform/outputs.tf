output "vpc_id" {
  description = "ID of the VPC."
  value       = aws_vpc.main.id
}

output "private_subnet_ids" {
  description = "Private subnet IDs (for ECS task placement)."
  value       = aws_subnet.private[*].id
}

output "database_subnet_ids" {
  description = "Database subnet IDs (internal, RDS + Redis)."
  value       = aws_subnet.database[*].id
}

output "rds_endpoint" {
  description = "PostgreSQL endpoint hostname."
  value       = aws_db_instance.main.address
}

output "rds_port" {
  description = "PostgreSQL port."
  value       = aws_db_instance.main.port
}

output "redis_primary_endpoint" {
  description = "Redis primary endpoint."
  value       = aws_elasticache_replication_group.main.primary_endpoint_address
}

output "redis_reader_endpoint" {
  description = "Redis reader endpoint."
  value       = aws_elasticache_replication_group.main.reader_endpoint_address
}

output "redis_auth_token" {
  description = "Redis AUTH token (store in AWS Secrets Manager; never commit)."
  value       = random_password.redis_auth.result
  sensitive   = true
}

output "s3_bucket_name" {
  description = "Main S3 bucket for app file storage."
  value       = aws_s3_bucket.main.id
}

output "s3_bucket_arn" {
  description = "Main S3 bucket ARN (for IAM policies)."
  value       = aws_s3_bucket.main.arn
}

output "s3_kms_key_arn" {
  description = "KMS key ARN for S3 encryption (for IAM policies)."
  value       = aws_kms_key.s3.arn
}
