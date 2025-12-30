#!/usr/bin/env python3
"""
Convert YAML registry files to JSON for PHP compatibility.
Run this script whenever registry YAML files are updated.
"""

import yaml
import json
import os
import sys

def convert_yaml_to_json(registry_path='registry'):
    """Convert all YAML files in registry to JSON."""
    yaml_files = [
        'adapters.yaml',
        'capabilities.yaml',
        'policy.yaml',
        'result_profiles.yaml',
        'ui.yaml'
    ]
    
    converted = []
    errors = []
    
    for yaml_file in yaml_files:
        yaml_path = os.path.join(registry_path, yaml_file)
        json_path = yaml_path.replace('.yaml', '.json')
        
        if not os.path.exists(yaml_path):
            errors.append(f"YAML file not found: {yaml_path}")
            continue
        
        try:
            with open(yaml_path, 'r') as f:
                data = yaml.safe_load(f)
            
            with open(json_path, 'w') as f:
                json.dump(data, f, indent=2)
            
            converted.append(yaml_file)
            print(f"✓ Converted {yaml_file} to JSON")
            
        except Exception as e:
            errors.append(f"Error converting {yaml_file}: {str(e)}")
    
    return converted, errors

if __name__ == '__main__':
    registry_path = sys.argv[1] if len(sys.argv) > 1 else 'registry'
    
    print(f"Converting YAML files in {registry_path}/")
    print("-" * 50)
    
    converted, errors = convert_yaml_to_json(registry_path)
    
    print("-" * 50)
    print(f"Converted {len(converted)} files")
    
    if errors:
        print("\nErrors:")
        for error in errors:
            print(f"  ✗ {error}")
        sys.exit(1)
    else:
        print("\n✓ All files converted successfully")
        sys.exit(0)
