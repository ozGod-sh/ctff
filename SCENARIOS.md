# Documentation des Scénarios des Défis

Ce document détaille la logique, les subtilités et les solutions pour chaque défi de l'Opération PHÉNIX.

---

## Défi 1 : Bienvenue, Agent

**Objectif :** Trouver le premier flag, caché de manière simple.

**Scénario :**
Le premier défi est une initiation classique aux CTF. La page d'accueil du défi semble simple, sans champ de saisie évident. L'objectif est d'inciter le participant à utiliser les outils de développement de son navigateur, une compétence fondamentale en cybersécurité web.

**Subtilités et Solution :**
1.  **Obfuscation du code source :** Le code de `defi1.html` est volontairement minifié et rempli de faux flags et de commentaires inutiles pour noyer l'information pertinente et d'un code dense et bizarre.
2.  **Le vrai indice :** L'utilisateur doit inspecter le code source (F12) et y trouver un commentaire contenant une chaîne encodée en Base64 : `<!-- Le premier flag est ici : WmFuZSBDaXBoZXI= -->`.
3.  **Décodage :** En utilisant un décodeur Base64, il obtient le nom du hacker : `Zane Cipher`.
4.  **Soumission :** Le flag à soumettre est `Zane Cipher`.

**Compétences testées :**
- Utilisation des outils de développement du navigateur.
- Inspection du code source côté client.

---

## Défi 2 : Le Mirage Numérique

**Objectif :** Exécuter une attaque XSS (Cross-Site Scripting) non triviale pour obtenir le flag.

**Scénario :**
Le défi se présente comme un moteur de recherche interne défectueux. L'interface suggère une faille XSS. Le but est de trouver un payload qui contourne les protections basiques pour déclencher la validation.

**Subtilités et Solution :**
1.  **Leurre (XSS simple) :** Si l'utilisateur tente un payload XSS classique comme `<script>alert('XSS')</script>`, le système le détecte et affiche un message ironique de Zane Cipher : *"Zane Cipher ricane. Tu pensais vraiment que ce serait aussi facile ?"*. Un indice l'oriente vers des vecteurs non conventionnels.
2.  **La Vraie Faille :** Le backend bloque les payloads contenant les balises `<script>` et les fonctions comme `alert()`, `prompt()`, etc. La solution attendue est un payload qui utilise des gestionnaires d'événements (`onerror`), des balises alternatives (`svg`, `img`) ou des schémas de données (`data:`, `javascript:`).
3.  **Solution type :** Un payload comme `<img src=x onerror=alert(1)>` ou `<svg onload=alert(1)>` contourne le filtre et valide le défi.
4.  **Le Flag :** Une fois le bon payload soumis, le système retourne le flag suivant : `PHENIX{Zane_IP:192.168.42.1_OS:ArchLinux_Kernel:6.6.6-zen}`. Ce flag fournit des informations cruciales pour la suite de l'enquête.

**Compétences testées :**
- Compréhension des failles XSS.
- Contournement de filtres de sécurité basiques (WAF-like).
- Utilisation de vecteurs d'attaque alternatifs.

---

## Défi 3 : Le Token Oublié (Mis à jour)

**Objectif :** Trouver et décoder un token JWT dissimulé dans le code source pour obtenir les informations de connexion SSH.

**Scénario :**
Le défi se présente comme une console de logs système d'un serveur compromis. Un flux continu de logs défile, créant du bruit visuel. L'objectif est de trouver une information critique laissée par le hacker, qui n'est pas directement visible dans le flux.

**Subtilités et Solution :**
1.  **Dissimulation avancée :** Le token n'est plus dans un commentaire HTML évident. Il est maintenant stocké dans un attribut `data-debug-token` sur l'une des lignes de log générées dynamiquement. L'utilisateur doit inspecter le DOM (les éléments HTML de la page) pour le trouver. Le texte de la ligne de log est anodin ("Token validation routine started.") pour ne pas attirer l'attention.
2.  **Trouver le token :** En inspectant le code source de la page `defi3.html`, l'utilisateur trouvera une ligne de log avec l'attribut spécial. Exemple : `<div class="log-line" data-debug-token="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoiemFuZS5jaXBoZXIiLCJwYXNzIjoiWmFuZTwzUGhlbml4XzIwMjQhIiwibm90ZSI6IlBlbnNlLWLDqnRlIDogZMOpcGxhY2VyIFNTSCBkdSBwb3J0IHBhciBkw6lmYXV0LiBOb3V2ZWF1IHBvcnQgZXN0IDIyMjIgwb3VyIElQIDE5Mi4xNjguNDIuMS4gR2FyZGVyIGNlIHRva2VuIGVuIHN1cmV0w6kuIn0.some_signature">...</div>`.
3.  **Décoder le JWT :** Le token peut être décodé avec un outil comme `jwt.io`. Le payload contient désormais non seulement le port et l'IP, mais aussi le mot de passe de connexion SSH.
    - **Payload :** `{ "user": "zane.cipher", "pass": "Zane<3Phenix_2024!", "note": "Pense-bête : déplacer SSH du port par défaut. Nouveau port est 2222 pour IP 192.168.42.1. Garder ce token en sureté." }`
4.  **Le Flag :** L'utilisateur doit assembler toutes les informations (port, IP, et mot de passe) pour construire le flag exact.
    - **Flag correct :** `PHENIX{SSH_PORT:2222_IP:192.168.42.1_PASS:Zane<3Phenix_2024!}`

**Compétences testées :**
- Analyse avancée du DOM et du code source.
- Compréhension de la dissimulation d'informations dans les attributs de données.
- Décodage de JWT et extraction d'informations complexes (multiples paires clé-valeur).
- Rigueur dans l'assemblage du flag final.

---

### Défi 4 : "Les Fragments d'un Deuil" (Logique Finale)

**Concept :** L'utilisateur doit se connecter au système de Zane Cipher via SSH et reconstituer une phrase de 8 fragments pour trouver la clé de désactivation d'une attaque. Chaque étape est un puzzle logique et technique qui révèle un fragment et donne un indice sémantique pour l'étape suivante.

**Phrase à reconstituer :** `ELLE-EST-PARTIE-A-JAMAIS-PAR-LEUR-FAUTE`

**Script final :** `/sbin/protocole_urgence.sh`

---

**Fragment 1 : ELLE**
*   **Lieu :** Répertoire personnel (`~`).
*   **Puzzle :** `ls -a` révèle un fichier nommé `...` (trois points). La commande `cat ...` échoue.
*   **Solution :** L'utilisateur doit utiliser un chemin relatif pour lire le fichier : `cat ./...`
*   **Contenu révélé :** "Pour **ELLE**, j'ai tout commencé. Mon premier rapport sur leur système corrompu est la preuve."
*   **Indice pour la suite :** La phrase "rapport sur leur système corrompu" pointe logiquement vers le répertoire de travail de Zane.

**Fragment 2 : EST**
*   **Lieu :** Répertoire `~/travail-phenix/`.
*   **Puzzle :** `ls` révèle un fichier nommé `-rapport.log`. La commande `cat -rapport.log` est interprétée comme une option et échoue.
*   **Solution :** L'utilisateur doit spécifier le chemin : `cat ./-rapport.log`.
*   **Contenu révélé :** "Leur système **EST** une façade. J'ai caché mes vraies archives ailleurs, là où on jette les choses inutiles."
*   **Indice pour la suite :** L'expression "là où on jette les choses inutiles" est une description claire pour un répertoire temporaire ou une poubelle.

**Fragment 3 : PARTIE**
*   **Lieu :** Répertoire `/var/tmp/poubelle/`.
*   **Puzzle :** Le répertoire est rempli de fichiers inutiles (binaires, vides). L'utilisateur doit trouver le seul fichier texte pertinent.
*   **Solution :** La commande `file /var/tmp/poubelle/*` identifie un seul fichier comme `ASCII text`. Ce fichier se nomme ` ` (un simple espace).
*   **Solution (2) :** Pour lire ce fichier, l'utilisateur doit utiliser des guillemets : `cat '/var/tmp/poubelle/ '`.
*   **Contenu révélé :** "Elle est **PARTIE**, et tout ce qui me reste sont des souvenirs encodés, comme cette vieille photo."
*   **Indice pour la suite :** Les mots "souvenirs" et "photo" pointent sans ambiguïté vers un répertoire de souvenirs.

**Fragment 4 : A**
*   **Lieu :** Répertoire `~/souvenirs/`.
*   **Puzzle :** Le répertoire contient un fichier `photo.jpg`. L'utilisateur doit en extraire les métadonnées ou les chaînes de caractères.
*   **Solution :** La commande `strings photo.jpg`.
*   **Contenu révélé :** "J'ai pensé **A** tout. Le mot de passe du prochain fragment est le seul mot de 10 lettres dans le dictionnaire de ce système."
*   **Indice pour la suite :** L'expression "dictionnaire de ce système" pointe directement vers le fichier `/usr/share/dict/words`.

**Fragment 5 : JAMAIS**
*   **Lieu :** Le fichier `/usr/share/dict/words`.
*   **Puzzle :** Trouver le seul mot de 10 lettres dans le fichier.
*   **Solution :** `grep -E '^.{10}$' /usr/share/dict/words`. La commande renvoie le mot `ressources`.
*   **Puzzle (2) :** Ce mot est un mot de passe. `ls -a ~` révèle une archive `secret.zip`.
*   **Solution (2) :** `unzip -P ressources secret.zip`. Cela extrait un fichier `note.txt`.
*   **Contenu révélé :** `cat note.txt` -> "Je ne les pardonnerai **JAMAIS**. Leur décision a été prise par des gens qui se cachent derrière des permissions spéciales."
*   **Indice pour la suite :** "permissions spéciales" est un indice technique fort pointant vers le bit SUID.

**Fragment 6 : PAR**
*   **Lieu :** Le système de fichiers global.
*   **Puzzle :** Trouver un fichier exécutable appartenant à `zane.cipher` avec des permissions SUID.
*   **Solution :** `find / -user zane.cipher -perm -4000 2>/dev/null`. La commande trouve `/usr/local/bin/lire_decision`.
*   **Solution (2) :** L'utilisateur exécute le programme : `./lire_decision`.
*   **Contenu révélé :** "Cette décision a été prise **PAR** des gens qui ne laissent que des traces illisibles. J'ai noté la référence de leur audit dans mes brouillons."
*   **Indice pour la suite :** La phrase pointe vers un répertoire "brouillons" et une "référence d'audit".

**Fragment 7 : LEUR**
*   **Lieu :** Répertoire `~/documents/brouillons/`.
*   **Puzzle :** Le répertoire contient un fichier `note_audit.txt`.
*   **Solution :** `cat note_audit.txt` -> "L'audit final est dans /var/log/audit-final.hex".
*   **Puzzle (2) :** Le fichier `audit-final.hex` contient une longue chaîne hexadécimale.
*   **Solution (2) :** `cat /var/log/audit-final.hex | xxd -r -p`.
*   **Contenu révélé :** "C'est de **LEUR** faute. La preuve finale est dans un fichier qui n'appartient à personne."
*   **Indice pour la suite :** "fichier qui n'appartient à personne" est un indice technique clair pour un fichier orphelin.

**Fragment 8 : FAUTE**
*   **Lieu :** Le système de fichiers global.
*   **Puzzle :** Trouver un fichier orphelin (sans utilisateur propriétaire).
*   **Solution :** `find / -nouser 2>/dev/null`. La commande trouve `/opt/archives/preuve.bz2`.
*   **Puzzle (2) :** Le fichier est compressé avec `bzip2`.
*   **Solution (2) :** `bzcat /opt/archives/preuve.bz2`.
*   **Contenu révélé :** Le mot `FAUTE`.
*   **Indice pour la suite :** Aucun. L'histoire est complète.
