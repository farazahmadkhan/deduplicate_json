<?php

// Read the JSON file
$jsonData = file_get_contents('leads.json');
$data = json_decode($jsonData, true);

// Initialize arrays for tracking
$processedLeads = [];
$changesLog = [];
$processedIds = [];
$processedEmails = [];

// Sort leads by entryDate in descending order (newest first)
usort($data['leads'], function ($a, $b) {
    return strtotime($b['entryDate']) - strtotime($a['entryDate']);
});

// Process each lead
foreach ($data['leads'] as $lead) {
    $id = $lead['_id'];
    $email = $lead['email'];

    // Check for duplicates using associative arrays for faster lookups
    $duplicateKey = null;
    $duplicateReason = null;

    if (isset($processedIds[$id])) {
        $duplicateKey = $processedIds[$id];
        $duplicateReason = 'ID';
    } elseif (isset($processedEmails[$email])) {
        $duplicateKey = $processedEmails[$email];
        $duplicateReason = 'Email';
    }

    if ($duplicateKey !== null) {
        // Log the change
        $existingLead = $processedLeads[$duplicateKey];
        $changes = [];

        // Compare fields and log changes
        foreach ($lead as $field => $value) {
            if ($value !== $existingLead[$field]) {
                $changes[] = [
                    'field' => $field,
                    'from' => $existingLead[$field],
                    'to' => $value
                ];
            }
        }

        $changesLog[] = [
            'type' => 'Duplicate Found',
            'reason' => $duplicateReason,
            'source' => $lead,
            'existing' => $existingLead,
            'changes' => $changes
        ];
    } else {
        // Add new lead to processed leads
        $processedLeads[] = $lead;
        $processedIds[$id] = count($processedLeads) - 1;
        $processedEmails[$email] = count($processedLeads) - 1;
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