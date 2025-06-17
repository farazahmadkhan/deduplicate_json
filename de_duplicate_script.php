<?php

// Read the JSON file
$jsonData = file_get_contents('leads.json');
$data = json_decode($jsonData, true);

// Initialize arrays for tracking
$processedLeads = [];
$changesLog = [];

// Sort leads by entryDate in descending order (newest first)
usort($data['leads'], function($a, $b) {
    return strtotime($b['entryDate']) - strtotime($a['entryDate']);
});

// Process each lead
foreach ($data['leads'] as $lead) {
    $id = $lead['_id'];
    $email = $lead['email'];
    
    // Check if we already have a record with this ID or email
    $isDuplicate = false;
    $duplicateReason = '';
    
    foreach ($processedLeads as $key => $processedLead) {
        if ($processedLead['_id'] === $id) {
            $isDuplicate = true;
            $duplicateReason = 'ID';
            $duplicateKey = $key;
            break;
        }
        if ($processedLead['email'] === $email) {
            $isDuplicate = true;
            $duplicateReason = 'Email';
            $duplicateKey = $key;
            break;
        }
    }
    
    if ($isDuplicate) {
        // Log the change
        $changesLog[] = [
            'type' => 'Duplicate Found',
            'reason' => $duplicateReason,
            'source' => $lead,
            'existing' => $processedLeads[$duplicateKey],
            'changes' => []
        ];
        
        // Compare fields and log changes
        foreach ($lead as $field => $value) {
            if ($lead[$field] !== $processedLeads[$duplicateKey][$field]) {
                $changesLog[count($changesLog) - 1]['changes'][] = [
                    'field' => $field,
                    'from' => $processedLeads[$duplicateKey][$field],
                    'to' => $value
                ];
            }
        }
    } else {
        // Add new lead to processed leads
        $processedLeads[] = $lead;
    }
}

// Create the output structure
$output = [
    'leads' => $processedLeads
];

// Save the deduplicated data
file_put_contents('deduplicated_leads.json', json_encode($output, JSON_PRETTY_PRINT));

// Save the changes log
file_put_contents('changes_log.json', json_encode($changesLog, JSON_PRETTY_PRINT));

// Output summary
echo "Processing complete!\n";
echo "Original leads count: " . count($data['leads']) . "\n";
echo "Deduplicated leads count: " . count($processedLeads) . "\n";
echo "Number of duplicates found: " . count($changesLog) . "\n";
echo "Results saved to deduplicated_leads.json\n";
echo "Changes log saved to changes_log.json\n";
?>