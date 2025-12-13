import mysql.connector
import json

# Configuration de la connexion
config = {
  'user': 'root',
  'password': '',
  'host': 'localhost',
  'database': 'colomap',
  'raise_on_warnings': True
}

try:
    cnx = mysql.connector.connect(**config)
    cursor = cnx.cursor(dictionary=True)

    # Exemple : Exporter les camps
    query = "SELECT nom, ville, prix FROM camps"
    cursor.execute(query)
    
    rows = cursor.fetchall()
    
    # Affichage en JSON
    print(json.dumps(rows, indent=4, ensure_ascii=False))

except mysql.connector.Error as err:
    print(f"Erreur: {err}")
finally:
    if 'cnx' in locals() and cnx.is_connected():
        cursor.close()
        cnx.close()