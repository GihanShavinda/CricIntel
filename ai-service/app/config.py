from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    app_name: str = "CricIntel Predictive Analytics"
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
