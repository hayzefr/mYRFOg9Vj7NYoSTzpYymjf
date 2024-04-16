CREATE DATABASE poker;

USE poker;

CREATE TABLE joueurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL
);

CREATE TABLE jours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    premier_id INT,
    deuxieme_id INT,
    troisieme_id INT,
    joueurs_sur_table TEXT,
    FOREIGN KEY (premier_id) REFERENCES joueurs(id),
    FOREIGN KEY (deuxieme_id) REFERENCES joueurs(id),
    FOREIGN KEY (troisieme_id) REFERENCES joueurs(id)
);

CREATE TABLE points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    joueur_id INT,
    points INT,
    FOREIGN KEY (joueur_id) REFERENCES joueurs(id)
);


