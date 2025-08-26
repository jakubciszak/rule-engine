<?php

/**
 * Sanctions and Compliance Screening Example for KYC Onboarding
 * 
 * This example demonstrates how to use the Rule Engine for sanctions screening
 * and compliance checks during KYC. It includes OFAC, EU, UN sanctions lists,
 * and other regulatory compliance requirements.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use JakubCiszak\RuleEngine\Api\NestedRuleApi;
use JakubCiszak\RuleEngine\Api\FlatRuleAPI;
use JakubCiszak\RuleEngine\Api\StringRuleApi;

echo "=== KYC Sanctions and Compliance Screening Example ===\n\n";

// Sample customer data for sanctions screening
$customers = [
    'clean_customer' => [
        'customer_id' => 'CLEAN001',
        'full_name' => 'John Michael Smith',
        'date_of_birth' => '1985-03-15',
        'nationality' => 'US',
        'country_of_residence' => 'US',
        'passport_number' => 'US123456789',
        'business_name' => null,
        'business_registration_number' => null,
        'sanctions_match_score' => 0,
        'ofac_screening_result' => 'no_match',
        'eu_sanctions_result' => 'no_match',
        'un_sanctions_result' => 'no_match',
        'pep_screening_result' => 'no_match',
        'adverse_media_score' => 1,
        'compliance_score' => 0,
        'compliance_status' => 'pending',
        'requires_manual_review' => false,
        'blocked_countries_exposure' => false,
        'high_risk_jurisdiction' => false,
        'sanctions_alerts' => [],
        'compliance_alerts' => [],
    ],
    'potential_sanctions_match' => [
        'customer_id' => 'MATCH001',
        'full_name' => 'Ahmed Hassan Mohamed',
        'date_of_birth' => '1975-08-22',
        'nationality' => 'EG',
        'country_of_residence' => 'EG',
        'passport_number' => 'EG987654321',
        'business_name' => null,
        'business_registration_number' => null,
        'sanctions_match_score' => 75,
        'ofac_screening_result' => 'potential_match',
        'eu_sanctions_result' => 'no_match',
        'un_sanctions_result' => 'potential_match',
        'pep_screening_result' => 'no_match',
        'adverse_media_score' => 15,
        'compliance_score' => 0,
        'compliance_status' => 'pending',
        'requires_manual_review' => false,
        'blocked_countries_exposure' => false,
        'high_risk_jurisdiction' => true,
        'sanctions_alerts' => [],
        'compliance_alerts' => [],
    ],
    'high_risk_business' => [
        'customer_id' => 'CORP001',
        'full_name' => 'Vladimir Petrov',
        'date_of_birth' => '1970-12-05',
        'nationality' => 'RU',
        'country_of_residence' => 'RU',
        'passport_number' => 'RU456789123',
        'business_name' => 'Energy Trading Ltd',
        'business_registration_number' => 'RU123456789',
        'sanctions_match_score' => 25,
        'ofac_screening_result' => 'no_match',
        'eu_sanctions_result' => 'potential_match',
        'un_sanctions_result' => 'no_match',
        'pep_screening_result' => 'close_associate',
        'adverse_media_score' => 8,
        'compliance_score' => 0,
        'compliance_status' => 'pending',
        'requires_manual_review' => false,
        'blocked_countries_exposure' => true,
        'high_risk_jurisdiction' => true,
        'sanctions_alerts' => [],
        'compliance_alerts' => [],
    ],
];

/**
 * Example 1: OFAC Sanctions Screening using NestedRuleApi
 * Office of Foreign Assets Control (OFAC) screening for US sanctions
 */
function ofacSanctionsScreening(array &$customer): bool
{
    $rules = [
        'ofac_exact_match' => [
            '==' => [['var' => 'ofac_screening_result'], 'exact_match'],
            'actions' => [
                '.compliance_score + 100',
                '.compliance_status = blocked',
                '.requires_manual_review = true',
                '.sanctions_alerts + OFAC_EXACT_MATCH'
            ]
        ],
        'ofac_potential_match_high' => [
            'and' => [
                ['==' => [['var' => 'ofac_screening_result'], 'potential_match'],
                ['>=' => [['var' => 'sanctions_match_score'], 80]]
            ],
            'actions' => [
                '.compliance_score + 80',
                '.requires_manual_review = true',
                '.sanctions_alerts + OFAC_HIGH_RISK_MATCH'
            ]
        ],
        'ofac_potential_match_medium' => [
            'and' => [
                ['==' => [['var' => 'ofac_screening_result'], 'potential_match'],
                ['>=' => [['var' => 'sanctions_match_score'], 50]],
                ['<' => [['var' => 'sanctions_match_score'], 80]]
            ],
            'actions' => [
                '.compliance_score + 50',
                '.requires_manual_review = true',
                '.sanctions_alerts + OFAC_MEDIUM_RISK_MATCH'
            ]
        ],
        'ofac_potential_match_low' => [
            'and' => [
                ['==' => [['var' => 'ofac_screening_result'], 'potential_match'],
                ['<' => [['var' => 'sanctions_match_score'], 50]]
            ],
            'actions' => [
                '.compliance_score + 20',
                '.sanctions_alerts + OFAC_LOW_RISK_MATCH'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 2: Multi-jurisdiction Sanctions Screening using StringRuleApi
 * Screening against EU, UN, and other international sanctions lists
 */
function multiJurisdictionScreening(array &$customer): bool
{
    $rules = [
        'eu_sanctions_hit' => "eu_sanctions_result == 'exact_match' or eu_sanctions_result == 'potential_match'",
        'un_sanctions_hit' => "un_sanctions_result == 'exact_match' or un_sanctions_result == 'potential_match'",
        'multiple_sanctions_hits' => "ofac_screening_result == 'potential_match' and eu_sanctions_result == 'potential_match'"
    ];

    $result = false;
    
    if (StringRuleApi::evaluate($rules['eu_sanctions_hit'], $customer)) {
        $customer['compliance_score'] += 60;
        $customer['requires_manual_review'] = true;
        $customer['sanctions_alerts'][] = 'EU_SANCTIONS_HIT';
        echo "  → EU sanctions list match detected (+60 compliance points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['un_sanctions_hit'], $customer)) {
        $customer['compliance_score'] += 70;
        $customer['requires_manual_review'] = true;
        $customer['sanctions_alerts'][] = 'UN_SANCTIONS_HIT';
        echo "  → UN sanctions list match detected (+70 compliance points)\n";
        $result = true;
    }
    
    if (StringRuleApi::evaluate($rules['multiple_sanctions_hits'], $customer)) {
        $customer['compliance_score'] += 30;
        $customer['compliance_status'] = 'high_risk';
        $customer['sanctions_alerts'][] = 'MULTIPLE_SANCTIONS_HITS';
        echo "  → Multiple sanctions list matches (+30 additional compliance points)\n";
        $result = true;
    }
    
    return $result;
}

/**
 * Example 3: PEP and Associate Screening using FlatRuleAPI
 * Screening for Politically Exposed Persons and their associates
 */
function pepAssociateScreening(array &$customer): bool
{
    $rules = [
        'rules' => [
            [
                'name' => 'direct_pep',
                'elements' => [
                    ['type' => 'variable', 'name' => 'pep_screening_result'],
                    ['type' => 'variable', 'name' => 'direct_pep', 'value' => 'direct_pep'],
                    ['type' => 'operator', 'name' => '=='],
                ],
                'actions' => [
                    '.compliance_score + 40',
                    '.requires_manual_review = true',
                    '.compliance_alerts + DIRECT_PEP_IDENTIFIED'
                ]
            ],
            [
                'name' => 'pep_family_member',
                'elements' => [
                    ['type' => 'variable', 'name' => 'pep_screening_result'],
                    ['type' => 'variable', 'name' => 'family_member', 'value' => 'family_member'],
                    ['type' => 'operator', 'name' => '=='],
                ],
                'actions' => [
                    '.compliance_score + 30',
                    '.requires_manual_review = true',
                    '.compliance_alerts + PEP_FAMILY_MEMBER'
                ]
            ],
            [
                'name' => 'pep_close_associate',
                'elements' => [
                    ['type' => 'variable', 'name' => 'pep_screening_result'],
                    ['type' => 'variable', 'name' => 'close_associate', 'value' => 'close_associate'],
                    ['type' => 'operator', 'name' => '=='],
                ],
                'actions' => [
                    '.compliance_score + 25',
                    '.compliance_alerts + PEP_CLOSE_ASSOCIATE'
                ]
            ]
        ]
    ];

    return FlatRuleAPI::evaluate($rules, $customer);
}

/**
 * Example 4: Geographic Risk Assessment using NestedRuleApi
 * Assessment based on customer's geographic exposure and high-risk jurisdictions
 */
function geographicComplianceRisk(array &$customer): bool
{
    $blockedCountries = ['KP', 'IR', 'CU', 'SY'];
    $highRiskCountries = ['AF', 'MM', 'PK', 'VE', 'BY'];
    $sanctionedJurisdictions = ['RU', 'CN']; // Partially sanctioned
    
    $rules = [
        'blocked_country_exposure' => [
            'in' => [['var' => 'country_of_residence'], $blockedCountries],
            'actions' => [
                '.compliance_score + 100',
                '.compliance_status = blocked',
                '.requires_manual_review = true',
                '.compliance_alerts + BLOCKED_JURISDICTION'
            ]
        ],
        'high_risk_jurisdiction' => [
            'in' => [['var' => 'country_of_residence'], $highRiskCountries],
            'actions' => [
                '.compliance_score + 35',
                '.high_risk_jurisdiction = true',
                '.compliance_alerts + HIGH_RISK_JURISDICTION'
            ]
        ],
        'sanctioned_jurisdiction' => [
            'in' => [['var' => 'country_of_residence'], $sanctionedJurisdictions],
            'actions' => [
                '.compliance_score + 45',
                '.requires_manual_review = true',
                '.compliance_alerts + SANCTIONED_JURISDICTION'
            ]
        ],
        'nationality_jurisdiction_mismatch' => [
            '!=' => [['var' => 'nationality'], ['var' => 'country_of_residence']],
            'actions' => [
                '.compliance_score + 10',
                '.compliance_alerts + NATIONALITY_RESIDENCE_MISMATCH'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Example 5: Adverse Media and Reputation Risk using StringRuleApi
 * Screening for negative media coverage and reputational risks
 */
function adverseMediaScreening(array &$customer): bool
{
    $rules = [
        'high_adverse_media' => 'adverse_media_score > 10',
        'medium_adverse_media' => 'adverse_media_score > 5 and adverse_media_score <= 10',
        'low_adverse_media' => 'adverse_media_score > 2 and adverse_media_score <= 5'
    ];

    $result = false;
    
    if (StringRuleApi::evaluate($rules['high_adverse_media'], $customer)) {
        $customer['compliance_score'] += 30;
        $customer['requires_manual_review'] = true;
        $customer['compliance_alerts'][] = 'HIGH_ADVERSE_MEDIA';
        echo "  → High adverse media coverage detected (+30 compliance points)\n";
        $result = true;
    } elseif (StringRuleApi::evaluate($rules['medium_adverse_media'], $customer)) {
        $customer['compliance_score'] += 15;
        $customer['compliance_alerts'][] = 'MEDIUM_ADVERSE_MEDIA';
        echo "  → Medium adverse media coverage detected (+15 compliance points)\n";
        $result = true;
    } elseif (StringRuleApi::evaluate($rules['low_adverse_media'], $customer)) {
        $customer['compliance_score'] += 5;
        $customer['compliance_alerts'][] = 'LOW_ADVERSE_MEDIA';
        echo "  → Low adverse media coverage detected (+5 compliance points)\n";
        $result = true;
    }
    
    return $result;
}

/**
 * Example 6: Business Entity Sanctions Screening
 */
function businessEntityScreening(array &$customer): bool
{
    if (empty($customer['business_name'])) {
        return false;
    }
    
    $rules = [
        'business_sanctions_screening' => [
            '!=' => [['var' => 'business_name'], null],
            'actions' => [
                '.compliance_score + 5',
                '.compliance_alerts + BUSINESS_ENTITY_SCREENING_REQUIRED'
            ]
        ]
    ];

    return NestedRuleApi::evaluate($rules, $customer);
}

/**
 * Function to determine final compliance status
 */
function determineFinalComplianceStatus(array $customer): string
{
    $score = $customer['compliance_score'];
    
    if ($score >= 100 || $customer['compliance_status'] === 'blocked') {
        return 'BLOCKED';
    } elseif ($score >= 80) {
        return 'HIGH_RISK_MANUAL_REVIEW';
    } elseif ($score >= 50 || $customer['requires_manual_review']) {
        return 'REQUIRES_MANUAL_REVIEW';
    } elseif ($score >= 20) {
        return 'ENHANCED_MONITORING';
    } else {
        return 'APPROVED';
    }
}

/**
 * Function to get compliance recommendations
 */
function getComplianceRecommendations(array $customer): array
{
    $recommendations = [];
    $status = determineFinalComplianceStatus($customer);
    
    switch ($status) {
        case 'BLOCKED':
            $recommendations[] = 'Customer must be blocked - do not proceed with onboarding';
            $recommendations[] = 'File Suspicious Activity Report (SAR) if required';
            $recommendations[] = 'Notify compliance team immediately';
            break;
        case 'HIGH_RISK_MANUAL_REVIEW':
            $recommendations[] = 'Senior compliance officer review required';
            $recommendations[] = 'Enhanced due diligence procedures mandatory';
            $recommendations[] = 'Continuous monitoring with monthly reviews';
            break;
        case 'REQUIRES_MANUAL_REVIEW':
            $recommendations[] = 'Compliance officer review required before approval';
            $recommendations[] = 'Additional documentation may be required';
            $recommendations[] = 'Enhanced monitoring recommended';
            break;
        case 'ENHANCED_MONITORING':
            $recommendations[] = 'Proceed with enhanced transaction monitoring';
            $recommendations[] = 'Quarterly compliance reviews';
            break;
        case 'APPROVED':
            $recommendations[] = 'Proceed with standard onboarding';
            $recommendations[] = 'Standard monitoring procedures apply';
            break;
    }
    
    return $recommendations;
}

/**
 * Function to format alerts for display
 */
function formatAlerts(array $alerts): array
{
    $alertMap = [
        'OFAC_EXACT_MATCH' => 'OFAC exact match - customer blocked',
        'OFAC_HIGH_RISK_MATCH' => 'OFAC high-risk potential match (80%+ similarity)',
        'OFAC_MEDIUM_RISK_MATCH' => 'OFAC medium-risk potential match (50-79% similarity)',
        'OFAC_LOW_RISK_MATCH' => 'OFAC low-risk potential match (<50% similarity)',
        'EU_SANCTIONS_HIT' => 'EU sanctions list match detected',
        'UN_SANCTIONS_HIT' => 'UN sanctions list match detected',
        'MULTIPLE_SANCTIONS_HITS' => 'Multiple sanctions list matches',
        'DIRECT_PEP_IDENTIFIED' => 'Direct PEP identified',
        'PEP_FAMILY_MEMBER' => 'PEP family member identified',
        'PEP_CLOSE_ASSOCIATE' => 'PEP close associate identified',
        'BLOCKED_JURISDICTION' => 'Customer from blocked jurisdiction',
        'HIGH_RISK_JURISDICTION' => 'Customer from high-risk jurisdiction',
        'SANCTIONED_JURISDICTION' => 'Customer from sanctioned jurisdiction',
        'NATIONALITY_RESIDENCE_MISMATCH' => 'Nationality and residence country mismatch',
        'HIGH_ADVERSE_MEDIA' => 'High level of adverse media coverage',
        'MEDIUM_ADVERSE_MEDIA' => 'Medium level of adverse media coverage',
        'LOW_ADVERSE_MEDIA' => 'Low level of adverse media coverage',
        'BUSINESS_ENTITY_SCREENING_REQUIRED' => 'Business entity requires additional screening'
    ];
    
    return array_map(function($alert) use ($alertMap) {
        return $alertMap[$alert] ?? $alert;
    }, $alerts);
}

// Process each customer through the sanctions and compliance screening pipeline
foreach ($customers as $customerType => $customer) {
    echo "Processing: " . str_replace('_', ' ', ucwords($customerType)) . " (ID: {$customer['customer_id']})\n";
    echo "Customer Information:\n";
    echo "  • Name: {$customer['full_name']}\n";
    echo "  • DOB: {$customer['date_of_birth']}\n";
    echo "  • Nationality: {$customer['nationality']}\n";
    echo "  • Residence: {$customer['country_of_residence']}\n";
    if ($customer['business_name']) {
        echo "  • Business: {$customer['business_name']}\n";
    }
    
    echo "\nScreening Results:\n";
    echo "  • OFAC: {$customer['ofac_screening_result']}\n";
    echo "  • EU Sanctions: {$customer['eu_sanctions_result']}\n";
    echo "  • UN Sanctions: {$customer['un_sanctions_result']}\n";
    echo "  • PEP Screening: {$customer['pep_screening_result']}\n";
    echo "  • Match Score: {$customer['sanctions_match_score']}%\n";
    echo "  • Adverse Media Score: {$customer['adverse_media_score']}\n";
    
    echo "\nCompliance Assessment:\n";
    
    // Run all screening checks
    ofacSanctionsScreening($customer);
    multiJurisdictionScreening($customer);
    pepAssociateScreening($customer);
    geographicComplianceRisk($customer);
    adverseMediaScreening($customer);
    businessEntityScreening($customer);
    
    // Determine final status and recommendations
    $finalStatus = determineFinalComplianceStatus($customer);
    $recommendations = getComplianceRecommendations($customer);
    $formattedSanctionsAlerts = formatAlerts($customer['sanctions_alerts']);
    $formattedComplianceAlerts = formatAlerts($customer['compliance_alerts']);
    
    echo "  → Compliance Score: {$customer['compliance_score']}\n";
    echo "  → Final Status: {$finalStatus}\n";
    echo "  → Manual Review Required: " . ($customer['requires_manual_review'] ? 'Yes' : 'No') . "\n";
    
    if (!empty($formattedSanctionsAlerts)) {
        echo "  → Sanctions Alerts:\n";
        foreach ($formattedSanctionsAlerts as $alert) {
            echo "    - {$alert}\n";
        }
    }
    
    if (!empty($formattedComplianceAlerts)) {
        echo "  → Compliance Alerts:\n";
        foreach ($formattedComplianceAlerts as $alert) {
            echo "    - {$alert}\n";
        }
    }
    
    echo "  → Recommendations:\n";
    foreach ($recommendations as $recommendation) {
        echo "    - {$recommendation}\n";
    }
    
    echo "\n" . str_repeat('-', 70) . "\n\n";
}

echo "=== Sanctions and Compliance Rules Summary ===\n";
echo "OFAC Screening:\n";
echo "  • Exact match: +100 points, customer blocked\n";
echo "  • High-risk potential match (80%+): +80 points, manual review\n";
echo "  • Medium-risk potential match (50-79%): +50 points, manual review\n";
echo "  • Low-risk potential match (<50%): +20 points\n\n";

echo "Multi-jurisdiction Screening:\n";
echo "  • EU sanctions hit: +60 points, manual review\n";
echo "  • UN sanctions hit: +70 points, manual review\n";
echo "  • Multiple hits: +30 additional points\n\n";

echo "PEP Screening:\n";
echo "  • Direct PEP: +40 points, manual review\n";
echo "  • PEP family member: +30 points, manual review\n";
echo "  • PEP close associate: +25 points\n\n";

echo "Geographic Risk:\n";
echo "  • Blocked jurisdiction: +100 points, customer blocked\n";
echo "  • High-risk jurisdiction: +35 points\n";
echo "  • Sanctioned jurisdiction: +45 points, manual review\n";
echo "  • Nationality/residence mismatch: +10 points\n\n";

echo "Adverse Media:\n";
echo "  • High (>10): +30 points, manual review\n";
echo "  • Medium (5-10): +15 points\n";
echo "  • Low (2-5): +5 points\n\n";

echo "Compliance Status Thresholds:\n";
echo "  • BLOCKED: 100+ points\n";
echo "  • HIGH_RISK_MANUAL_REVIEW: 80+ points\n";
echo "  • REQUIRES_MANUAL_REVIEW: 50+ points or manual review flag\n";
echo "  • ENHANCED_MONITORING: 20+ points\n";
echo "  • APPROVED: <20 points\n";

/**
 * This example demonstrates:
 * 1. Comprehensive sanctions screening workflows
 * 2. Multi-jurisdiction compliance requirements
 * 3. Risk-based scoring and decision making
 * 4. Automated alert generation and categorization
 * 5. Manual review triggers and escalation paths
 * 6. Real-world regulatory compliance scenarios
 * 7. Geographic and reputational risk assessment
 */