# Rule Engine Examples

This directory contains real-world business examples demonstrating how to use the Rule Engine library for various business scenarios.

## KYC Onboarding Risk Calculation

The KYC (Know Your Customer) examples demonstrate how to use the Rule Engine for financial compliance and risk assessment during customer onboarding. These examples cover:

### 1. Basic Risk Scoring (`KYC/BasicRiskScoring.php`)
- Customer age verification
- Income-based risk assessment
- Geographic risk evaluation
- Employment status checks

### 2. Document Verification (`KYC/DocumentVerification.php`)
- ID document type validation
- Document expiry checks
- Address verification requirements
- Multiple document validation

### 3. Enhanced Due Diligence (`KYC/EnhancedDueDiligence.php`)
- High-value transaction monitoring
- PEP (Politically Exposed Person) screening
- Source of funds verification
- Additional documentation requirements

### 4. Sanctions and Compliance (`KYC/SanctionsCompliance.php`)
- Sanctions list screening
- Country-based restrictions
- Compliance status tracking
- Automated flagging system

### 5. Final Risk Assessment (`KYC/FinalRiskAssessment.php`)
- Comprehensive risk scoring
- Multiple factor combination
- Risk level determination (Low/Medium/High)
- Action-based score calculation

## How to Run the Examples

Each example file contains:
- Sample customer data
- Rule definitions using different APIs
- Expected outcomes
- Usage instructions

To run an example:

```php
<?php
require_once 'vendor/autoload.php';

// Include the example file
include 'Examples/KYC/BasicRiskScoring.php';

// The example will output the results
?>
```

## API Demonstrations

The examples showcase all three Rule Engine APIs:

- **NestedRuleApi**: For complex business logic with nested conditions
- **FlatRuleAPI**: For performance-critical rule evaluation
- **StringRuleApi**: For human-readable rule expressions

## Business Context

These examples simulate a real financial institution's KYC onboarding process, including:

- **Regulatory Compliance**: Meeting AML (Anti-Money Laundering) requirements
- **Risk Management**: Automated risk scoring and categorization
- **Operational Efficiency**: Automated decision making for standard cases
- **Audit Trail**: Clear rule evaluation and decision tracking

## Sample Customer Profiles

The examples use realistic customer profiles representing different risk categories:

- **Low Risk**: Young professional with stable income and clean background
- **Medium Risk**: Self-employed individual with moderate complexity
- **High Risk**: High-net-worth individual requiring enhanced due diligence

Each profile demonstrates different rule evaluation paths and outcomes.