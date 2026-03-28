#!/usr/bin/env python3
"""Extract OpenAPI specs from Wildberries dev portal pages."""

import sys
import re
import json
import subprocess

SECTIONS = [
    "content",
    "marketplace",
    "prices",
    "analytics",
    "statistics",
    "feedbacks-questions",
    "returns",
    "promotion",
    "documents",
    "finance",
    "tariffs",
    "recommendations",
    "buyers-chat",
    "dp-calendar",
    "api-information",
    "reports",
    "orders-fbs",
    "orders-dbs",
    "in-store-pickup",
    "supply",
]


def fetch_and_extract(section):
    url = f"https://dev.wildberries.ru/openapi/{section}"
    try:
        result = subprocess.run(
            ["curl", "-s", "-L", "--max-time", "60", url],
            capture_output=True, text=True, timeout=70
        )
        html = result.stdout
    except Exception as e:
        print(f"  ERROR fetching {section}: {e}", file=sys.stderr)
        return None

    if not html or len(html) < 1000:
        print(f"  Empty or small response for {section}", file=sys.stderr)
        return None

    # Concatenate all __next_f.push chunks
    chunks = re.findall(r'self\.__next_f\.push\(\[1,"(.*?)"\]\)', html, re.DOTALL)
    if not chunks:
        print(f"  No __next_f chunks found for {section}", file=sys.stderr)
        return None

    full = ''.join(chunks)

    # Find __redoc_state
    idx = full.find('__redoc_state')
    if idx == -1:
        print(f"  __redoc_state not found for {section}", file=sys.stderr)
        return None

    segment = full[idx:]
    brace = segment.find('{')
    text = segment[brace:]

    # Decode JS string escaping by treating as JSON string content
    try:
        decoded = json.loads('"' + text.split('};')[0] + '}"')
    except json.JSONDecodeError:
        # Try alternate split
        try:
            decoded = json.loads('"' + text.split('"};\\n')[0] + '"}"')
        except:
            print(f"  Could not decode JS string for {section}", file=sys.stderr)
            return None

    # Parse the decoded JSON
    try:
        decoder = json.JSONDecoder()
        state, _ = decoder.raw_decode(decoded)
    except json.JSONDecodeError as e:
        print(f"  JSON parse error for {section}: {e}", file=sys.stderr)
        return None

    # Extract the actual OpenAPI spec from redoc state
    spec = None
    if isinstance(state, dict):
        if 'spec' in state and isinstance(state['spec'], dict):
            spec = state['spec'].get('data')
        elif 'openapi' in state:
            spec = state

    if not spec or 'paths' not in spec:
        print(f"  No valid OpenAPI spec found for {section}", file=sys.stderr)
        return None

    return spec


def main():
    target = sys.argv[1] if len(sys.argv) > 1 else None
    sections = [target] if target else SECTIONS

    all_specs = {}
    total_endpoints = 0

    for section in sections:
        print(f"\n{'='*60}", file=sys.stderr)
        print(f"Fetching {section}...", file=sys.stderr)
        spec = fetch_and_extract(section)
        if spec:
            all_specs[section] = spec
            with open(f"/home/user/sellermind/docs/wb_api/{section}.json", "w") as f:
                json.dump(spec, f, indent=2, ensure_ascii=False)

            paths = spec.get("paths", {})
            endpoint_count = 0
            print(f"  Title: {spec.get('info', {}).get('title', 'N/A')}", file=sys.stderr)
            print(f"  Endpoints: {len(paths)}", file=sys.stderr)

            for path, methods in sorted(paths.items()):
                for method in sorted(methods.keys()):
                    if method in ('get', 'post', 'put', 'patch', 'delete'):
                        op = methods[method]
                        summary = op.get('summary', op.get('operationId', 'N/A'))
                        print(f"    {method.upper():6s} {path} — {summary}", file=sys.stderr)
                        endpoint_count += 1

            total_endpoints += endpoint_count
            print(f"  Total for {section}: {endpoint_count} methods", file=sys.stderr)
        else:
            print(f"  FAILED: {section}", file=sys.stderr)

    # Save combined specs
    with open("/home/user/sellermind/docs/wb_api/_all_specs.json", "w") as f:
        json.dump(all_specs, f, indent=2, ensure_ascii=False)

    print(f"\n{'='*60}", file=sys.stderr)
    print(f"TOTAL: {len(all_specs)} API sections, {total_endpoints} endpoints", file=sys.stderr)


if __name__ == "__main__":
    main()
