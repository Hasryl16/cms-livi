import os
from fastapi import Header, HTTPException


async def verify_api_key(x_api_key: str = Header(None)):
    if x_api_key is None:
        raise HTTPException(status_code=401, detail="X-API-Key header required")
    if x_api_key != os.getenv("CMS_API_KEY"):
        raise HTTPException(status_code=403, detail="Invalid API key")
