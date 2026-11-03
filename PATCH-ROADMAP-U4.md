### U4 — Artifact sources and verification

- [x] Add provider-specific exact-host HTTPS allow-list policy.
- [x] Add bounded streaming artifact download to private temporary staging files.
- [x] Verify SHA-256 against official registry metadata.
- [x] Require detached Ed25519 signature verification using operator-pinned public keys.
- [x] Add unit tests for host policy, checksum mismatch, missing signature, and valid signature.
- [ ] Configure production Pixely artifact host and trusted public keys before enabling downloads.
- [ ] Integrate with the concrete official registry response contract when that contract is finalised.
- [ ] Keep extraction, installation, migrations, and rollback out of U4; implement these in U5.
