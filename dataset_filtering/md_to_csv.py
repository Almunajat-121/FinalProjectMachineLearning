import csv

input_file = r"d:\kuliah\semester 6\ML\FinalProjectMachineLearning\dataset_filtering\tes.md"
output_file = r"d:\kuliah\semester 6\ML\FinalProjectMachineLearning\dataset_filtering\tes.csv"

with open(input_file, 'r', encoding='utf-8') as f:
    lines = f.readlines()

data_rows = []
header = None

for line in lines:
    line = line.strip()
    if line.startswith('|') and line.endswith('|'):
        # Parse the row
        row = [cell.strip() for cell in line.strip('|').split('|')]
        
        # Check if it's the header
        if row == ['text', 'category', 'urgency']:
            if not header:
                header = row
            continue
            
        # Check if it's a separator
        if all(cell.strip('-') == '' for cell in row):
            continue
            
        # It's a data row
        # Clean up <br> to actual newlines if any, unescape \|
        row = [cell.replace('<br>', '\n').replace('\\|', '|') for cell in row]
        data_rows.append(row)

with open(output_file, 'w', encoding='utf-8', newline='') as f:
    writer = csv.writer(f)
    if header:
        writer.writerow(header)
    else:
        # Fallback header if none found
        writer.writerow(['text', 'category', 'urgency'])
    writer.writerows(data_rows)

print(f"Cleaned {len(data_rows)} rows and saved to {output_file}")
