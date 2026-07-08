from dotenv import load_dotenv
load_dotenv()

from fastapi import FastAPI, Depends
from db import lifespan
from auth import verify_api_key
from routers import config as config_router

app = FastAPI(title="LIVI AI API", lifespan=lifespan)

app.include_router(
    config_router.router,
    prefix="/api",
    dependencies=[Depends(verify_api_key)],
)


@app.get("/health")
async def health():
    return {"status": "ok"}
