<?php

/**
 * Enhanced Due Diligence (EDD) Example for KYC Onboarding
 * 
 * This example demonstrates how to use the Rule Engine for Enhanced Due Diligence
 * requirements. EDD is triggered for high-risk customers and involves additional
 * scrutiny, documentation, and ongoing monitoring.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use JakubCiszak\RuleEngine\Api\NestedRuleApi;
use JakubCiszak\RuleEngine\Api\FlatRuleAPI;
use JakubCiszak\RuleEngine\Api\StringRuleApi;

echo "=== KYC Enhanced Due Diligence Example ===\n\n";

// Sample customer data for EDD assessment
$customers = [
    'high_value_individual' => [
        'customer_id' => 'HNW001',
        'customer_type' => 'individual',
        'net_worth' => 5000000,
        'annual_income' => 800000,
        'expected_monthly_transactions' => 50000,
        'initial_deposit' => 250000,
        'occupation' => 'real_estate_investor',
        'country_of_residence' => 'US',
        'country_of_citizenship' => 'US',
        'is_pep' => false,
        'has_pep_connections' => true,
        'source_of_funds' => 'business_income',
        'source_of_wealth' => 'inheritance_and_business',
        'business_nature' => 'real_estate_development',
        'years_in_business' => 15,
        'has_criminal_record' => false,
        'adverse_media_hits' => 2,
        'sanctions_screening_result' => 'clear',
        'edd_score' => 0,
        'edd_required' => false,
        'additional_documentation_required' => [],
        'monitoring_level' => 'standard',
    ],
    'foreign_pep' => [
        'customer_id' => 'PEP001',
        'customer_type' => 'individual',
        'net_worth' => 2000000,
        'annual_income' => 300000,
        'expected_monthly_transactions' => 75000,
        'initial_deposit' => 500000,
        'occupation' => 'former_government_official',
        'country_of_residence' => 'BR',
        'country_of_citizenship' => 'BR',
        'is_pep' => true,
        'has_pep_connections' => true,
        'source_of_funds' => 'government_pension',
        'source_of_wealth' => 'government_salary',
        'business_nature' => null,
        'years_in_business' => 0,
        'has_criminal_record' => false,
        'adverse_media_hits' => 5,
        'sanctions_screening_result' => 'clear',
        'edd_score' => 0,
        'edd_required' => false,
        'additional_documentation_required' => [],
        'monitoring_level' => 'standard',
    ],
    'complex_business_structure' => [
        'customer_id' => 'CORP001',
        'customer_type' => 'business',
        'net_worth' => 10000000,
        'annual_income' => 15000000,
        'expected_monthly_transactions' => 500000,
        'initial_deposit' => 1000000,
        'occupation' => 'international_trading',
        'country_of_residence' => 'SG',
        'country_of_citizenship' => 'SG',
        'is_pep' => false,
        'has_pep_connections' => false,
        'source_of_funds' => 'trading_profits',
        'source_of_wealth' => 'business_operations',
        'business_nature' => 'commodity_trading',
        'years_in_business' => 8,
        'has_criminal_record' => false,
        'adverse_media_hits' => 1,
        'sanctions_screening_result' => 'clear',
        'edd_score' => 0,
        'edd_required' => false,
        'additional_documentation_required' => [],
        'monitoring_level' => 'standard',
    ],
];

/**
 * Example 1: PEP (Politically Exposed Person) Assessment using NestedRuleApi
 * PEPs require enhanced due diligence due to corruption risks
 */
function pepAssessment(array &$customer): bool
{
    $rules = [
        'direct_pep' => [
            '==' => [['var' => 'is_pep'], true],
            'actions' => [
                '.edd_score + 50',
                '.edd_required = true',
                '.monitoring_level = enhanced',
                '.additional_documentation_required + source_of_wealth_evidence'
            ]
        ],
        'pep_connections' => [
            'and' => [
                ['==' => [['var' => 'has_pep_connections'], true]],
                ['==' => [['var' => 'is_pep'], false]]
            ],
            'actions' => [
                '.edd_score + 25',
                '.additional_documentation_required + relationship_documentation'
            ]
        ],
        'high_adverse_media' => [
            '>' => [['var' => 'adverse_media_hits'], 3],
            'actions' => [
                '.edd_score + 20',
                '.additional_documentation_required + adverse_media_explanation'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 2: High-Value Transaction Assessment using StringRuleApi
 * Large transactions or deposits trigger enhanced scrutiny
 */
function highValueAssessment(array &$customer): bool
{
    $rules = [
        'high_net_worth' => 'net_worth > 1000000',
        'large_initial_deposit' => 'initial_deposit > 100000',
        'high_monthly_volume' => 'expected_monthly_transactions > 50000',
        'income_deposit_mismatch' => 'initial_deposit > annual_income * 0.5'
    ];

    $result = false;
    
    if (StringRuleApi::evaluate($rules['high_net_worth'], $customer)) {
        $customer['edd_score'] += 20;
        $customer['additional_documentation_required'][] = 'wealth_verification';
        echo "  → High net worth detected (+20 EDD points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['large_initial_deposit'], $customer)) {
        $customer['edd_score'] += 15;
        $customer['additional_documentation_required'][] = 'source_of_funds_documentation';
        echo "  → Large initial deposit (+15 EDD points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['high_monthly_volume'], $customer)) {
        $customer['edd_score'] += 10;
        $customer['monitoring_level'] = 'enhanced';
        echo "  → High expected transaction volume (+10 EDD points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['income_deposit_mismatch'], $customer)) {
        $customer['edd_score'] += 25;
        $customer['edd_required'] = true;
        $customer['additional_documentation_required'][] = 'detailed_source_verification';
        echo "  → Initial deposit exceeds reasonable income proportion (+25 EDD points)\n";
        $result = true;
    }
    
    return $result;
}

/**
 * Example 3: Geographic and Business Risk Assessment using FlatRuleAPI
 * Certain countries and business types require enhanced due diligence
 */
function geographicBusinessRiskAssessment(array &$customer): bool
{
    $highRiskCountries = ['AF', 'IR', 'KP', 'MM', 'PK'];
    $highRiskBusinesses = ['money_service_business', 'precious_metals', 'art_dealer', 'casino'];
    $complexBusinesses = ['international_trading', 'commodity_trading', 'investment_advisory'];
    
    $rules = [
        'rules' => [
            [
                'name' => 'high_risk_country',
                'elements' => [
                    ['type' => 'variable', 'name' => 'country_of_residence'],
                    ['type' => 'variable', 'name' => 'high_risk_countries', 'value' => $highRiskCountries],
                    ['type' => 'operator', 'name' => 'in'],
                ],
                'actions' => [
                    '.edd_score + 40',
                    '.edd_required = true',
                    '.monitoring_level = enhanced',
                    '.additional_documentation_required + enhanced_country_documentation'
                ]
            ],
            [
                'name' => 'high_risk_business',
                'elements' => [
                    ['type' => 'variable', 'name' => 'business_nature'],
                    ['type' => 'variable', 'name' => 'high_risk_businesses', 'value' => $highRiskBusinesses],
                    ['type' => 'operator', 'name' => 'in'],
                ],
                'actions' => [
                    '.edd_score + 35',
                    '.edd_required = true',
                    '.additional_documentation_required + business_license_verification'
                ]
            ],
            [
                'name' => 'complex_business',
                'elements' => [
                    ['type' => 'variable', 'name' => 'business_nature'],
                    ['type' => 'variable', 'name' => 'complex_businesses', 'value' => $complexBusinesses],
                    ['type' => 'operator', 'name' => 'in'],
                ],
                'actions' => [
                    '.edd_score + 20',
                    '.additional_documentation_required + business_structure_documentation'
                ]
            ],
            [
                'name' => 'new_business',
                'elements' => [
                    ['type' => 'variable', 'name' => 'years_in_business'],
                    ['type' => 'variable', 'name' => 'min_years', 'value' => 2],
                    ['type' => 'operator', 'name' => '<'],
                ],
                'actions' => [
                    '.edd_score + 15',
                    '.additional_documentation_required + business_plan_and_projections'
                ]
            ]
        ]
    ];

    return FlatRuleAPI::evaluate($rules, $customer);
}

/**
 * Example 4: Source of Funds and Wealth Verification using NestedRuleApi
 * Validates the legitimacy of customer's financial resources
 */
function sourceVerificationAssessment(array &$customer): bool
{
    $suspiciousSources = ['cryptocurrency', 'gambling', 'cash_intensive_business'];
    $complexSources = ['inheritance_and_business', 'multiple_sources', 'investment_returns'];
    
    $rules = [
        'suspicious_source_of_funds' => [
            'in' => [['var' => 'source_of_funds'], $suspiciousSources],
            'actions' => [
                '.edd_score + 30',
                '.edd_required = true',
                '.additional_documentation_required + detailed_source_verification'
            ]
        ],
        'complex_source_of_wealth' => [
            'in' => [['var' => 'source_of_wealth'], $complexSources],
            'actions' => [
                '.edd_score + 15',
                '.additional_documentation_required + wealth_composition_breakdown'
            ]
        ],
        'government_source_verification' => [
            'and' => [
                ['==' => [['var' => 'source_of_funds'], 'government_pension']],
                ['==' => [['var' => 'is_pep'], true]]
            ],
            'actions' => [
                '.edd_score + 25',
                '.additional_documentation_required + government_employment_verification'
            ]
        ],
        'inconsistent_occupation_wealth' => [
            'and' => [
                ['in' => [['var' => 'occupation'], ['student', 'unemployed', 'retired']]],
                ['>' => [['var' => 'net_worth'], 500000]]
            ],
            'actions' => [
                '.edd_score + 35',
                '.edd_required = true',
                '.additional_documentation_required + wealth_source_explanation'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 5: Customer Type and Entity Structure Assessment
 */
function entityStructureAssessment(array &$customer): bool
{
    $rules = [
        'business_customer' => [
            '==' => [['var' => 'customer_type'], 'business'],
            'actions' => [
                '.edd_score + 10',
                '.additional_documentation_required + corporate_documents',
                '.additional_documentation_required + beneficial_ownership_documentation'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Function to determine final EDD requirements
 */
function determineFinalEddRequirements(array $customer): array
{
    $eddRequired = $customer['edd_required'] || $customer['edd_score'] >= 40;
    $monitoringLevel = $customer['monitoring_level'];
    
    if ($customer['edd_score'] >= 80) {
        $monitoringLevel = 'intensive';
    } elseif ($customer['edd_score'] >= 50) {
        $monitoringLevel = 'enhanced';
    }
    
    return [
        'edd_required' => $eddRequired,
        'monitoring_level' => $monitoringLevel,
        'approval_required' => $customer['edd_score'] >= 60 ? 'senior_management' : 
                              ($customer['edd_score'] >= 40 ? 'compliance_officer' : 'none'),
        'review_frequency' => match($monitoringLevel) {
            'intensive' => 'monthly',
            'enhanced' => 'quarterly',
            'standard' => 'annually'
        }
    ];
}

/**
 * Function to get required documentation list
 */
function getRequiredDocumentation(array $customer): array
{
    $docs = array_unique($customer['additional_documentation_required']);
    
    $documentationMap = [
        'source_of_wealth_evidence' => 'Source of Wealth Statement with supporting documents',
        'relationship_documentation' => 'Documentation of PEP relationships',
        'adverse_media_explanation' => 'Written explanation of adverse media mentions',
        'wealth_verification' => 'Independent wealth verification (accountant letter, asset valuations)',
        'source_of_funds_documentation' => 'Bank statements, salary certificates, business records',
        'detailed_source_verification' => 'Detailed source of funds verification with audit trail',
        'enhanced_country_documentation' => 'Enhanced documentation for high-risk country exposure',
        'business_license_verification' => 'Business licenses and regulatory approvals',
        'business_structure_documentation' => 'Corporate structure charts and ownership details',
        'business_plan_and_projections' => 'Business plan and financial projections',
        'wealth_composition_breakdown' => 'Detailed breakdown of wealth composition',
        'government_employment_verification' => 'Government employment and pension verification',
        'wealth_source_explanation' => 'Detailed explanation of wealth sources',
        'corporate_documents' => 'Articles of incorporation, bylaws, board resolutions',
        'beneficial_ownership_documentation' => 'Beneficial ownership identification and verification'
    ];
    
    return array_map(function($key) use ($documentationMap) {
        return $documentationMap[$key] ?? $key;
    }, $docs);
}

// Process each customer through the EDD assessment pipeline
foreach ($customers as $customerType => $customer) {
    echo "Processing: " . str_replace('_', ' ', ucwords($customerType)) . " (ID: {$customer['customer_id']})\n";
    echo "Customer Profile:\n";
    echo "  • Type: {$customer['customer_type']}\n";
    echo "  • Net Worth: \$" . number_format($customer['net_worth']) . "\n";
    echo "  • Annual Income: \$" . number_format($customer['annual_income']) . "\n";
    echo "  • Initial Deposit: \$" . number_format($customer['initial_deposit']) . "\n";
    echo "  • Country: {$customer['country_of_residence']}\n";
    echo "  • Occupation/Business: {$customer['occupation']}\n";
    echo "  • PEP Status: " . ($customer['is_pep'] ? 'Yes' : 'No') . "\n";
    
    echo "\nEDD Assessment Results:\n";
    
    // Run all EDD assessments
    pepAssessment($customer);
    highValueAssessment($customer);
    geographicBusinessRiskAssessment($customer);
    sourceVerificationAssessment($customer);
    entityStructureAssessment($customer);
    
    // Determine final requirements
    $eddRequirements = determineFinalEddRequirements($customer);
    $requiredDocs = getRequiredDocumentation($customer);
    
    echo "  → EDD Score: {$customer['edd_score']}\n";
    echo "  → EDD Required: " . ($eddRequirements['edd_required'] ? 'Yes' : 'No') . "\n";
    echo "  → Monitoring Level: {$eddRequirements['monitoring_level']}\n";
    echo "  → Approval Required: {$eddRequirements['approval_required']}\n";
    echo "  → Review Frequency: {$eddRequirements['review_frequency']}\n";
    
    if (!empty($requiredDocs)) {
        echo "  → Additional Documentation Required:\n";
        foreach ($requiredDocs as $doc) {
            echo "    - {$doc}\n";
        }
    }
    
    echo "\n" . str_repeat('-', 70) . "\n\n";
}

echo "=== Enhanced Due Diligence Rules Summary ===\n";
echo "PEP Assessment:\n";
echo "  • Direct PEP: +50 points, EDD required, enhanced monitoring\n";
echo "  • PEP connections: +25 points\n";
echo "  • High adverse media (>3 hits): +20 points\n\n";

echo "High-Value Assessment:\n";
echo "  • High net worth (>$1M): +20 points\n";
echo "  • Large initial deposit (>$100K): +15 points\n";
echo "  • High monthly volume (>$50K): +10 points\n";
echo "  • Income/deposit mismatch: +25 points, EDD required\n\n";

echo "Geographic/Business Risk:\n";
echo "  • High-risk country: +40 points, EDD required\n";
echo "  • High-risk business: +35 points, EDD required\n";
echo "  • Complex business: +20 points\n";
echo "  • New business (<2 years): +15 points\n\n";

echo "Source Verification:\n";
echo "  • Suspicious source of funds: +30 points, EDD required\n";
echo "  • Complex source of wealth: +15 points\n";
echo "  • Government source + PEP: +25 points\n";
echo "  • Inconsistent occupation/wealth: +35 points, EDD required\n\n";

echo "EDD Thresholds:\n";
echo "  • EDD Required: 40+ points\n";
echo "  • Compliance Officer Approval: 40+ points\n";
echo "  • Senior Management Approval: 60+ points\n";
echo "  • Monitoring Levels: Standard/Enhanced/Intensive\n";

/**
 * This example demonstrates:
 * 1. Complex EDD trigger conditions and scoring
 * 2. Multiple risk factors working together
 * 3. Automated documentation requirements
 * 4. Escalation and approval workflows
 * 5. Ongoing monitoring level determination
 * 6. Real-world financial compliance scenarios
 * 7. Risk-based approach to customer due diligence
 */