<?php

/**
 * Basic Risk Scoring Example for KYC Onboarding
 * 
 * This example demonstrates how to use the Rule Engine for basic customer risk assessment
 * during the KYC onboarding process. It evaluates fundamental customer data to determine
 * initial risk scores.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use JakubCiszak\RuleEngine\Api\NestedRuleApi;
use JakubCiszak\RuleEngine\Api\FlatRuleAPI;
use JakubCiszak\RuleEngine\Api\StringRuleApi;

echo "=== KYC Basic Risk Scoring Example ===\n\n";

// Sample customer data representing different risk profiles
$customers = [
    'low_risk_customer' => [
        'age' => 28,
        'annual_income' => 75000,
        'country' => 'US',
        'employment_status' => 'employed',
        'employment_years' => 3,
        'has_criminal_record' => false,
        'credit_score' => 720,
        'risk_score' => 0, // Will be calculated by rules
    ],
    'medium_risk_customer' => [
        'age' => 45,
        'annual_income' => 150000,
        'country' => 'BR',
        'employment_status' => 'self_employed',
        'employment_years' => 8,
        'has_criminal_record' => false,
        'credit_score' => 650,
        'risk_score' => 0,
    ],
    'high_risk_customer' => [
        'age' => 19,
        'annual_income' => 500000,
        'country' => 'PK',
        'employment_status' => 'unemployed',
        'employment_years' => 0,
        'has_criminal_record' => true,
        'credit_score' => 580,
        'risk_score' => 0,
    ],
];

/**
 * Example 1: Age-based Risk Assessment using NestedRuleApi
 * Young customers (under 21) or very old customers (over 80) get higher risk scores
 */
function ageRiskAssessment(array &$customer): bool
{
    $rules = [
        'high_age_risk' => [
            'or' => [
                ['<' => [['var' => 'age'], 21]],
                ['>' => [['var' => 'age'], 80]]
            ],
            'actions' => ['.risk_score + 25']
        ],
        'medium_age_risk' => [
            'and' => [
                ['>=' => [['var' => 'age'], 21]],
                ['<' => [['var' => 'age'], 25]]
            ],
            'actions' => ['.risk_score + 10']
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 2: Income-based Risk Assessment using StringRuleApi
 * Very high income without corresponding employment raises suspicion
 */
function incomeRiskAssessment(array &$customer): bool
{
    $rules = [
        'suspicious_income' => 'annual_income > 300000 and employment_status == unemployed',
        'high_income_risk' => 'annual_income > 200000 and employment_years < 2',
    ];

    $result = false;
    
    if (StringRuleApi::evaluate($rules['suspicious_income'], $customer)) {
        $customer['risk_score'] += 50;
        $result = true;
        echo "  → Suspicious income pattern detected (+50 risk points)\n";
    }
    
    if (StringRuleApi::evaluate($rules['high_income_risk'], $customer)) {
        $customer['risk_score'] += 20;
        $result = true;
        echo "  → High income with short employment history (+20 risk points)\n";
    }
    
    return $result;
}

/**
 * Example 3: Geographic Risk Assessment using FlatRuleAPI
 * Countries with higher financial crime rates get higher risk scores
 */
function geographicRiskAssessment(array &$customer): bool
{
    $highRiskCountries = ['AF', 'PK', 'IR', 'KP', 'MM'];
    $mediumRiskCountries = ['BR', 'IN', 'RU', 'CN', 'TR'];
    
    $rules = [
        'rules' => [
            [
                'name' => 'high_risk_country',
                'elements' => [
                    ['type' => 'variable', 'name' => 'country'],
                    ['type' => 'variable', 'name' => 'high_risk_list', 'value' => $highRiskCountries],
                    ['type' => 'operator', 'name' => 'in'],
                ],
                'actions' => ['.risk_score + 30']
            ],
            [
                'name' => 'medium_risk_country',
                'elements' => [
                    ['type' => 'variable', 'name' => 'country'],
                    ['type' => 'variable', 'name' => 'medium_risk_list', 'value' => $mediumRiskCountries],
                    ['type' => 'operator', 'name' => 'in'],
                ],
                'actions' => ['.risk_score + 15']
            ]
        ]
    ];

    return FlatRuleAPI::evaluate($rules, $customer);
}

/**
 * Example 4: Employment and Credit Risk Assessment using NestedRuleApi
 */
function employmentCreditRiskAssessment(array &$customer): bool
{
    $rules = [
        'unemployment_risk' => [
            '==' => [['var' => 'employment_status'], 'unemployed'],
            'actions' => ['.risk_score + 20']
        ],
        'criminal_record_risk' => [
            '==' => [['var' => 'has_criminal_record'], true],
            'actions' => ['.risk_score + 40']
        ],
        'poor_credit_risk' => [
            '<' => [['var' => 'credit_score'], 600],
            'actions' => ['.risk_score + 25']
        ],
        'excellent_credit_bonus' => [
            '>' => [['var' => 'credit_score'], 750],
            'actions' => ['.risk_score - 10']
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Function to determine risk level based on total score
 */
function getRiskLevel(int $score): string
{
    if ($score >= 80) return 'HIGH';
    if ($score >= 40) return 'MEDIUM';
    return 'LOW';
}

// Process each customer through the risk assessment pipeline
foreach ($customers as $customerType => $customer) {
    echo "Processing: " . str_replace('_', ' ', ucwords($customerType)) . "\n";
    echo "Initial Data: Age={$customer['age']}, Income=\${$customer['annual_income']}, " .
         "Country={$customer['country']}, Employment={$customer['employment_status']}\n";
    echo "Risk Assessment Results:\n";
    
    // Run all risk assessments
    ageRiskAssessment($customer);
    incomeRiskAssessment($customer);
    geographicRiskAssessment($customer);
    employmentCreditRiskAssessment($customer);
    
    // Determine final risk level
    $riskLevel = getRiskLevel($customer['risk_score']);
    
    echo "  → Total Risk Score: {$customer['risk_score']}\n";
    echo "  → Risk Level: {$riskLevel}\n";
    
    // Add recommendations based on risk level
    switch ($riskLevel) {
        case 'LOW':
            echo "  → Recommendation: Approve with standard monitoring\n";
            break;
        case 'MEDIUM':
            echo "  → Recommendation: Approve with enhanced monitoring\n";
            break;
        case 'HIGH':
            echo "  → Recommendation: Require manual review and additional documentation\n";
            break;
    }
    
    echo "\n" . str_repeat('-', 70) . "\n\n";
}

echo "=== Risk Scoring Rules Summary ===\n";
echo "• Age Risk: Under 21 or over 80 (+25), Age 21-24 (+10)\n";
echo "• Income Risk: High income + unemployed (+50), High income + short employment (+20)\n";
echo "• Geographic Risk: High-risk countries (+30), Medium-risk countries (+15)\n";
echo "• Employment Risk: Unemployed (+20), Criminal record (+40)\n";
echo "• Credit Risk: Poor credit <600 (+25), Excellent credit >750 (-10)\n";
echo "• Risk Levels: 0-39 (LOW), 40-79 (MEDIUM), 80+ (HIGH)\n";

/**
 * This example demonstrates:
 * 1. Using multiple Rule Engine APIs for different types of logic
 * 2. Action-based scoring system that accumulates risk points
 * 3. Complex business rules for financial compliance
 * 4. Real-world risk factors used in KYC processes
 * 5. Different customer profiles and their risk assessments
 */