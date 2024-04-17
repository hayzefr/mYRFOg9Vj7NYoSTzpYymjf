-- Il faut créer les tables, une par une et à dans cet ordre : joueurs, puis jours, puis points.

CREATE TABLE joueurs (
  id SERIAL PRIMARY KEY,
  nom VARCHAR(50) NOT NULL
);

CREATE TABLE jours (
  id SERIAL PRIMARY KEY,
  date DATE NOT NULL,
  premier_id INTEGER REFERENCES joueurs(id),
  deuxieme_id INTEGER REFERENCES joueurs(id),
  troisieme_id INTEGER REFERENCES joueurs(id),
  joueurs_sur_table TEXT[]
);

CREATE TABLE points (
  id SERIAL PRIMARY KEY,
  jour_id INTEGER REFERENCES jours(id),
  joueur_id INTEGER REFERENCES joueurs(id),
  points INTEGER NOT NULL
);