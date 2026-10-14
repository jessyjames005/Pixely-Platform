#!/usr/bin/env python3
"""Build the GitHub wiki pages from the repository documentation.

Usage: python3 scripts/build-wiki.py [output_dir]   (default: build/wiki)

The repository docs stay the source of truth; the wiki is generated from them.
Relative links between docs are rewritten to wiki links, links to other
repository files point to GitHub, and every page states its source file.
"""
import posixpath
import re
import sys
from pathlib import Path

REPO_URL = "https://github.com/jessyjames005/Pixely-Platform"
BRANCH = "develop"
ROOT = Path(__file__).resolve().parent.parent
OUT = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "build" / "wiki"

# source file -> (wiki page name, sidebar section)
PAGES = {
    "VISION.md": ("Vision", "Overview"),
    "ARCHITECTURE.md": ("Architecture", "Overview"),
    "ROADMAP.md": ("Roadmap", "Overview"),
    "CHANGELOG.md": ("Changelog", "Overview"),
    "docs/architecture/boot-process.md": ("Boot-Process", "Architecture"),
    "docs/architecture/contracts.md": ("Contracts", "Architecture"),
    "docs/architecture/data-model.md": ("Data-Model", "Architecture"),
    "docs/architecture/frontend-architecture.md": ("Frontend-Architecture", "Architecture"),
    "docs/architecture/extension-manager.md": ("Extension-Manager", "Architecture"),
    "docs/architecture/extension-manifest.md": ("Extension-Manifest", "Architecture"),
    "docs/architecture/extension-sdk-v2-capabilities.md": ("Extension-SDK-v2-Capabilities", "Architecture"),
    "docs/architecture/extension-sdk-v2-surface-structure.md": ("Extension-SDK-v2-Surfaces", "Architecture"),
    "docs/architecture/diagrams/boot-process.md": ("Diagram-Boot-Process", "Diagrams"),
    "docs/architecture/diagrams/contracts.md": ("Diagram-Contracts", "Diagrams"),
    "docs/architecture/diagrams/data-model.md": ("Diagram-Data-Model", "Diagrams"),
    "docs/architecture/diagrams/extension-manager.md": ("Diagram-Extension-Manager", "Diagrams"),
    "docs/handbook/core/authentication.md": ("Handbook-Authentication", "Handbook"),
    "docs/handbook/core/extension-management.md": ("Handbook-Extension-Management", "Handbook"),
    "docs/handbook/core/extension-state-persistence.md": ("Handbook-Extension-State-Persistence", "Handbook"),
    "docs/handbook/extensions/lifecycle.md": ("Handbook-Extension-Lifecycle", "Handbook"),
    "docs/handbook/extensions/dependencies.md": ("Handbook-Extension-Dependencies", "Handbook"),
    "docs/handbook/extensions/dependency-safety.md": ("Handbook-Extension-Dependency-Safety", "Handbook"),
    "docs/api/query-parameters.md": ("API-Query-Parameters", "API"),
    "docs/api/examples.md": ("API-Examples", "API"),
    "docs/development/PROJECT_RULES.md": ("Project-Rules", "Development"),
    "docs/development/architecture-principles.md": ("Architecture-Principles", "Development"),
    "docs/development/continuous-integration.md": ("Continuous-Integration", "Development"),
}
for adr in sorted((ROOT / "docs/architecture/adr").glob("ADR-*.md")):
    num = adr.stem.split("-")[1]
    PAGES[f"docs/architecture/adr/{adr.name}"] = (f"ADR-{num}", "Decisions (ADR)")

LINK = re.compile(r"(\]\()([^)\s]+)(\))")


def rewrite(match, src):
    target = match.group(2)
    if re.match(r"^(https?:|mailto:|#)", target):
        return match.group(0)
    path, _, anchor = target.partition("#")
    resolved = posixpath.normpath(posixpath.join(posixpath.dirname(src), path))
    suffix = f"#{anchor}" if anchor else ""
    if resolved in PAGES:
        return f"{match.group(1)}{PAGES[resolved][0]}{suffix}{match.group(3)}"
    kind = "tree" if (ROOT / resolved).is_dir() else "blob"
    return f"{match.group(1)}{REPO_URL}/{kind}/{BRANCH}/{resolved}{suffix}{match.group(3)}"


def convert(src):
    text = (ROOT / src).read_text(encoding="utf-8").replace("\r\n", "\n")
    text = LINK.sub(lambda m: rewrite(m, src), text)
    banner = (
        f"> _This page is generated from [`{src}`]({REPO_URL}/blob/{BRANCH}/{src}). "
        "Edit it in the repository, not in the wiki: wiki edits are overwritten._\n\n"
    )
    return banner + text


def main():
    OUT.mkdir(parents=True, exist_ok=True)
    for old in OUT.glob("*.md"):
        old.unlink()
    for src, (name, _) in PAGES.items():
        if not (ROOT / src).exists():
            print(f"skip (missing): {src}")
            continue
        (OUT / f"{name}.md").write_text(convert(src), encoding="utf-8")

    sections = {}
    for src, (name, section) in PAGES.items():
        if (ROOT / src).exists():
            sections.setdefault(section, []).append(name)

    titles = {
        n: re.sub(r"^ADR-\d+\s*[:\u2013-]\s*", "", (ROOT / s).read_text(encoding="utf-8").splitlines()[0].lstrip("# ").strip())
        for s, (n, sec) in PAGES.items()
        if sec == "Decisions (ADR)" and (ROOT / s).exists()
    }

    def entry(name):
        if name in titles:
            return f"- [{name.replace('-', ' ')}: {titles[name]}]({name})"
        return f"- [{name.replace('-', ' ')}]({name})"

    order = ["Overview", "Architecture", "Handbook", "API", "Development", "Decisions (ADR)", "Diagrams"]
    sidebar = ["**[Home](Home)**\n"]
    for section in order:
        if section in sections:
            sidebar.append(f"**{section}**\n")
            sidebar.extend(entry(n) for n in sections[section])
            sidebar.append("")
    sidebar.append(f"**[Repository]({REPO_URL})**")
    (OUT / "_Sidebar.md").write_text("\n".join(sidebar) + "\n", encoding="utf-8")

    (OUT / "_Footer.md").write_text(
        f"Pixely Platform · [Repository]({REPO_URL}) · "
        f"[Issues]({REPO_URL}/issues) · MIT License\n",
        encoding="utf-8",
    )

    adrs = "\n".join(
        f"- [{(ROOT / s).read_text(encoding='utf-8').splitlines()[0].lstrip('# ').strip()}]({n})"
        for s, (n, sec) in PAGES.items()
        if sec == "Decisions (ADR)" and (ROOT / s).exists()
    )
    home = f"""# Pixely Platform

> **Build once. Extend forever.**

Pixely Platform is an open-source modular platform built with Laravel and Vue. A small **Core**
provides shared services (authentication, users, roles and permissions, settings, localization,
API, extension engine) and every business feature is an independent **Extension** that plugs into
the Core without modifying it.

## Start here

- [Vision](Vision) — why Pixely is a platform and not an application
- [Architecture](Architecture) — layers, Core, Extension Manager
- [Roadmap](Roadmap) and [Changelog](Changelog) — where the project is and where it goes
- [Project Rules](Project-Rules) — the rules every contribution follows
- [Continuous Integration](Continuous-Integration) — pipeline, quality gates, how to fix a red build

## Working with the platform

| I want to... | Read |
| --- | --- |
| Understand how extensions are discovered and booted | [Extension Manager](Extension-Manager), [Boot Process](Boot-Process) |
| Write an extension | [Extension Manifest](Extension-Manifest), [Extension SDK v2 Capabilities](Extension-SDK-v2-Capabilities), [Surfaces](Extension-SDK-v2-Surfaces) |
| Manage an extension's lifecycle and dependencies | [Lifecycle](Handbook-Extension-Lifecycle), [Dependencies](Handbook-Extension-Dependencies) |
| Call or extend the API | [Query Parameters](API-Query-Parameters), [Examples](API-Examples) |
| Understand authentication | [Authentication](Handbook-Authentication) |
| Understand the frontend | [Frontend Architecture](Frontend-Architecture) |
| Understand the data | [Data Model](Data-Model) |

## Architecture Decision Records

{adrs}

## Contributing

`develop` is protected: changes go through a pull request and the CI must pass.
See [Continuous Integration]( Continuous-Integration) and [Project Rules](Project-Rules).

## Technology

Laravel · PHP · MySQL · Redis · Vue 3 · TypeScript · Vite · Vuetify · Pest/PHPUnit · PHPStan ·
ESLint · Docker · GitHub Actions
""".replace("( Continuous-Integration)", "(Continuous-Integration)")
    (OUT / "Home.md").write_text(home, encoding="utf-8")
    print(f"{len(list(OUT.glob('*.md')))} files written to {OUT}")


if __name__ == "__main__":
    main()
