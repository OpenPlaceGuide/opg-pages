<?php

function jsonToYaml($json)
{
    $data = json_decode($json, true);
    $yaml = "osmBranches:\n";

    foreach ($data['elements'] as $element) {
        $yaml .= "  - type: {$element['type']}\n";
        $yaml .= "    id: {$element['id']}\n";
    }

    return $yaml;
}

// Read the JSON data from the file
$json_data = file_get_contents('places.json');

// Convert the JSON to YAML format
$yaml_output = jsonToYaml($json_data);

// Print the YAML output
echo $yaml_output;
