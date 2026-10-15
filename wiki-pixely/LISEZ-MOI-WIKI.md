# Mise en place du wiki GitHub

Le wiki est généré à partir de `docs/` (source de vérité) par `scripts/build-wiki.py`.

## Mise en place (une fois)

1. Sur GitHub : Settings > General > Features > cocher **Wikis**.
2. Onglet **Wiki** > « Create the first page » > enregistrer une page vide (cela crée le dépôt `Pixely-Platform.wiki.git`).
3. En local, depuis le dépôt :

```bash
python3 scripts/build-wiki.py build/wiki
git clone git@github.com:jessyjames005/Pixely-Platform.wiki.git ../Pixely-Platform.wiki
rsync -a --delete --exclude .git build/wiki/ ../Pixely-Platform.wiki/
cd ../Pixely-Platform.wiki
git add -A && git commit -m "Initial wiki" && git push
```

(Le dépôt wiki n'est pas soumis aux règles de `develop` : le push direct fonctionne. Sa branche s'appelle `master`.)

## Mise à jour

- Manuelle : relancer les commandes ci-dessus.
- Automatique : `.github/workflows/wiki-sync.yml` (déclenchement manuel dans l'onglet Actions). Pour publier à chaque push sur `develop` qui modifie la doc, créer la variable de dépôt `WIKI_SYNC` = `true`.

Ne modifie pas les pages directement sur GitHub : elles seraient écrasées à la prochaine synchronisation. Pour ajouter une page, l'ajouter au dictionnaire `PAGES` de `scripts/build-wiki.py`.
