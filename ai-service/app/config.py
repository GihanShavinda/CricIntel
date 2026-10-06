from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    app_name: str = "CricIntel Intelligence Service"
    app_env: str = "local"
    app_host: str = "127.0.0.1"
    app_port: int = 8100
    database_url: str = (
        "postgresql+psycopg://cricintel_user:YourStrongPassword123"
        "@127.0.0.1:5433/cricintel"
    )
    model_dir: str = "models"
    min_batter_rows: int = 80
    min_bowler_rows: int = 80
    min_team_rows: int = 80
    random_state: int = 42

    strategy_assistant_enabled: bool = False
    llm_provider: str = "disabled"
    llm_base_url: str = "http://127.0.0.1:11434/v1"
    llm_api_key: str = ""
    llm_model: str = ""
    llm_timeout_seconds: int = 45
    llm_temperature: float = 0.1
    llm_max_tokens: int = 1200

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    @property
    def model_path(self) -> Path:
        path = Path(self.model_dir)
        path.mkdir(parents=True, exist_ok=True)
        return path


settings = Settings()
