#!/usr/bin/env python3
"""
Polymarket CLOB execution sidecar.

The Laravel app delegates EIP-712 order signing to this script so that the
Polygon private key never enters PHP. It reads a JSON request on stdin and
writes a JSON response on stdout. The wallet key is read from the environment
(POLYMARKET_PRIVATE_KEY) and is never logged.

Requests:
  {"action": "place_order", "token_id": "...", "side": "BUY",
   "price": 0.62, "size": 10, "host": "...", "signature_type": 0,
   "funder": "0x..."}
  {"action": "balance", "host": "...", "signature_type": 0, "funder": "0x..."}

Responses:
  {"ok": true, "order_id": "...", "status": "..."}
  {"ok": true, "balance": 123.45}
  {"ok": false, "error": "..."}
"""
import json
import os
import sys


def fail(message):
    print(json.dumps({"ok": False, "error": str(message)}))
    sys.exit(0)


def build_client(req):
    try:
        from py_clob_client.client import ClobClient
    except ImportError:
        fail("py-clob-client not installed (pip install -r sidecar/requirements.txt)")

    key = os.environ.get("POLYMARKET_PRIVATE_KEY")
    if not key:
        fail("POLYMARKET_PRIVATE_KEY env var is not set")

    host = req.get("host", "https://clob.polymarket.com")
    signature_type = int(req.get("signature_type", 0))
    funder = req.get("funder") or os.environ.get("POLYMARKET_FUNDER")

    kwargs = {"key": key, "chain_id": 137, "signature_type": signature_type}
    if funder:
        kwargs["funder"] = funder

    client = ClobClient(host, **kwargs)
    client.set_api_creds(client.create_or_derive_api_creds())
    return client


def place_order(req):
    from py_clob_client.clob_types import OrderArgs, OrderType
    from py_clob_client.order_builder.constants import BUY, SELL

    client = build_client(req)
    side = BUY if str(req.get("side", "BUY")).upper() == "BUY" else SELL

    order_args = OrderArgs(
        price=float(req["price"]),
        size=float(req["size"]),
        side=side,
        token_id=str(req["token_id"]),
    )
    signed = client.create_order(order_args)
    resp = client.post_order(signed, OrderType.GTC)

    return {
        "ok": bool(resp.get("success", True)),
        "order_id": resp.get("orderID") or resp.get("orderId"),
        "status": resp.get("status", "submitted"),
        "raw": resp,
    }


def balance(req):
    from py_clob_client.clob_types import AssetType, BalanceAllowanceParams

    client = build_client(req)
    params = BalanceAllowanceParams(asset_type=AssetType.COLLATERAL)
    resp = client.get_balance_allowance(params)
    # USDC has 6 decimals.
    raw = float(resp.get("balance", 0))
    return {"ok": True, "balance": raw / 1_000_000 if raw > 1000 else raw}


def main():
    try:
        req = json.loads(sys.stdin.read() or "{}")
    except json.JSONDecodeError as exc:
        fail(f"invalid request json: {exc}")
        return

    action = req.get("action")
    try:
        if action == "place_order":
            result = place_order(req)
        elif action == "balance":
            result = balance(req)
        else:
            result = {"ok": False, "error": f"unknown action: {action}"}
    except Exception as exc:  # noqa: BLE001 - report any failure as JSON
        result = {"ok": False, "error": str(exc)}

    print(json.dumps(result))


if __name__ == "__main__":
    main()
