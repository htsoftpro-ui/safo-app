#!/usr/bin/env python3
"""Upload a static directory to an FTP/FTPS hosting account."""
from __future__ import annotations

import os
import posixpath
import sys
from ftplib import FTP_TLS, error_perm
from pathlib import Path


def remote_dir(ftp: FTP_TLS, path: str) -> None:
    if path in ("", "."):
        return
    for part in Path(path).parts:
        if part in (".", "/"):
            continue
        try:
            ftp.cwd(part)
        except error_perm:
            ftp.mkd(part)
            ftp.cwd(part)


def upload_tree(local_root: Path, ftp: FTP_TLS) -> int:
    uploaded = 0
    for local_path in sorted(local_root.rglob("*")):
        relative = local_path.relative_to(local_root).as_posix()
        parent = posixpath.dirname(relative)
        ftp.cwd("/")
        remote_dir(ftp, parent)
        if local_path.is_dir():
            try:
                ftp.mkd(local_path.name)
            except error_perm:
                pass
            continue
        with local_path.open("rb") as stream:
            ftp.storbinary(f"STOR {local_path.name}", stream, blocksize=64 * 1024)
        uploaded += 1
        print(f"uploaded {relative}", flush=True)
    return uploaded


def main() -> int:
    if len(sys.argv) != 2:
        print("usage: deploy_ftp.py DIRECTORY", file=sys.stderr)
        return 2
    root = Path(sys.argv[1]).resolve()
    if not root.is_dir():
        print(f"directory not found: {root}", file=sys.stderr)
        return 2

    host = os.environ["FTP_HOST"]
    user = os.environ["FTP_USER"]
    password = os.environ["FTP_PASSWORD"]
    remote = os.environ.get("FTP_REMOTE_DIR", "htdocs")
    port = int(os.environ.get("FTP_PORT", "21"))

    ftp = FTP_TLS(timeout=60)
    ftp.connect(host, port)
    ftp.login(user, password)
    ftp.prot_p()
    ftp.cwd("/")
    remote_dir(ftp, remote)
    count = upload_tree(root, ftp)
    ftp.quit()
    print(f"uploaded {count} files to {host}/{remote}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
