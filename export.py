import requests

api_key = 'patirCyGLZzXOZQla.dd78066f77fd3b02324df287029066dea37afabe29ce6a261b501bf739b41e53'
base_id = 'app0AetG6XFed8k2B'
table_name = 'MaTable'
url = f'https://api.airtable.com/v0/{base_id}/{table_name}'

headers = {
    'Authorization': f'Bearer {api_key}',
    'Content-Type': 'application/json'
}

data = {
    "records": [
        {"fields": {"Nom": "Alice", "Âge": 30}},
        {"fields": {"Nom": "Bob", "Âge": 25}},
    ]
}

response = requests.post(url, headers=headers, json=data)
print(response.json())
