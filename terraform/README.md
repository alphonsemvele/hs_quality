# QualitéDomicile — Infrastructure (Terraform)

Terraform scaffold for the QualitéDomicile production + staging environments.
Target: **AWS France (Paris) — `eu-west-3`**, which is HDS-certified.

## What this scaffold creates

| Resource | File | Purpose |
|---|---|---|
| VPC + subnets + security groups | `network.tf` | Network isolation, public/private subnet split |
| RDS PostgreSQL 16 (multi-AZ) | `rds.tf` | Source-of-truth database, automated backups, RPO < 1h |
| ElastiCache Redis (cluster mode) | `redis.tf` | Cache, queues, WebSocket pub-sub |
| S3 bucket with SSE-KMS | `s3.tf` | File storage (photos, signatures, reports) |

**Not yet scaffolded** (added when container images exist in Phase 1+):
- ECS Fargate cluster + service
- Application Load Balancer
- CloudFront distribution
- Route53 DNS records
- ACM certificates

## Prerequisites

1. AWS account with an IAM user that has admin/Terraform permissions
2. Terraform >= 1.6 installed (`brew install terraform` or official installer)
3. AWS CLI configured (`aws configure` or `~/.aws/credentials`)
4. A state backend — **strongly recommended**: an S3 bucket + DynamoDB
   table for Terraform state locking. See `backend.tf.example`.

## One-time setup

```bash
cd terraform

# Copy and customize the example tfvars (DO NOT commit the real tfvars file)
cp staging.auto.tfvars.example staging.auto.tfvars
# Edit staging.auto.tfvars — set project_name, db_password, etc.

# Initialize Terraform (downloads providers, configures backend)
terraform init

# Review what will be created
terraform plan -var-file=staging.auto.tfvars

# Apply when ready
terraform apply -var-file=staging.auto.tfvars
```

## Cost expectations (rough, monthly)

| Resource | Staging | Production (Phase 1) |
|---|---|---|
| RDS PostgreSQL (db.t4g.small, multi-AZ) | ~€55 | ~€180 (db.t4g.medium multi-AZ) |
| ElastiCache Redis (cache.t4g.micro) | ~€15 | ~€50 (cache.t4g.small, 2 nodes) |
| S3 + data transfer | <€5 | ~€20 |
| ECS Fargate (when wired) | ~€40 | ~€150 |
| ALB | ~€20 | ~€25 |
| **Total** | **~€135** | **~€425** |

Scaling up for Phase 3+ (200 structures) roughly 3x production baseline.
Phase 5 multi-region adds separate deployments per country.

## HDS compliance notes

Every resource that holds health-related data has encryption at rest:
- RDS uses AWS-managed KMS encryption (can swap to customer-managed CMK)
- S3 uses SSE-KMS with a dedicated project KMS key
- Redis encryption at rest enabled on cluster mode

Hosting in `eu-west-3` (Paris) ensures data residency. AWS Paris region is
HDS-certified; keep all traffic intra-region. No cross-region replication
should carry personal data without explicit review.

## Next steps (when ECS + ALB are added)

1. Build Docker image, push to ECR
2. Add `ecs.tf` with task definition referencing the ECR image
3. Add `alb.tf` with target group + HTTPS listener
4. Add ACM certificate for the staging/prod domain
5. Add Route53 records pointing to the ALB

Until then, this scaffold provisions the data plane; the app layer runs
locally against it or can be deployed via alternative (Laravel Forge,
simple EC2 + Nginx, etc.) during very early Phase 1.
