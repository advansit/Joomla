# Agent Instructions — Advans IT Solutions GmbH

## Git-Workflow
- Nie direkt auf `main` committen oder pushen. Jede Änderung über einen Feature-Branch + PR.
- Branch-Namen kurz & beschreibend: `fix/...`, `feat/...`, `docs/...`, `chore/...`.
- Den PR niemals selbst mergen — das macht der Maintainer.
- Squash-Merge; Branch wird nach dem Merge gelöscht.

## Commit-Konventionen
- Commit-Email immer: `89843389+advansit@users.noreply.github.com`
- Commit-Name immer:  `Advans IT Solutions GmbH`
- Niemals `@advans.ch`-Adressen verwenden (GitHub blockiert den Push).
- Diese Adresse ist an den verifizierten GPG-Signing-Key gebunden. Signing aktivieren,
  WENN der Key in der Umgebung vorhanden ist (`git config commit.gpgsign true`);
  andernfalls unsigniert committen — kein Commit darf am fehlenden Key scheitern.
- Conventional Commits: `fix:` → Patch, `feat:` → Minor, `feat!:`/`BREAKING CHANGE:` → Major.
  Scope optional (`fix(scope): ...`).
- Kein `Co-authored-by`-Trailer und keine Agent-Signatur (kein "Ona", "Copilot" o. ä.).

## Skills
Detailwissen liegt in `.claude/skills/`. Vor repo-spezifischen Aufgaben den passenden Skill lesen.

## Repo-spezifisch (Joomla)

**Dieses öffentliche Repo erzwingt verifizierte GPG-Signaturen** (Branch Protection). Signing ist hier verpflichtend — nicht optional. Die UID des Signing-Keys muss exakt der Commit-E-Mail entsprechen, sonst lehnt GitHub den Merge ab.

- Signing-Key ist an `89843389+advansit@users.noreply.github.com` gebunden. Eine andere E-Mail erzeugt eine Signatur, die GitHub nicht verifizieren kann.
- Inhalt: generische Joomla-Extensions (`plg_ajax_joomlaajaxforms`, `plg_osmap_j2commerce`, J2Commerce-Extensions).
- Release-CI leitet die Version aus dem Conventional-Commit-Prefix ab — nur `fix(...)` und `feat(...)` werden erkannt; andere Prefixe überspringen den Release.
- Skills `joomla-extensions` und `privacy-plugin` liegen in `.claude/skills/`.
