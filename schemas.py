from pydantic import BaseModel


class ConfigValue(BaseModel):
    value: str
    updated_by: str
    updated_at: str


class ConfigResponse(BaseModel):
    system_prompt: ConfigValue | None = None
    welcome_message: ConfigValue | None = None
    lead_trigger_keywords: ConfigValue | None = None


class ConfigUpdateRequest(BaseModel):
    key: str
    value: str
    updated_by: str
