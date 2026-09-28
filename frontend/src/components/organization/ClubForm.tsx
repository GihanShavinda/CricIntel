import axios from "axios";
import { useRef, useState, type ChangeEvent, type FormEvent } from "react";

interface Props {
  onSubmit: (form: FormData) => Promise<void>;
  onCancel: () => void;
}

type ValidationErrors = Record<string, string[]>;

const MAX_LOGO_SIZE = 4 * 1024 * 1024; // 4 MB

const ALLOWED_LOGO_TYPES = ["image/jpeg", "image/png", "image/webp"];

export function ClubForm({ onSubmit, onCancel }: Props) {
  const [submitting, setSubmitting] = useState(false);

  const [message, setMessage] = useState("");

  const [errors, setErrors] = useState<ValidationErrors>({});

  const [logoName, setLogoName] = useState("");

  const fileInputRef = useRef<HTMLInputElement | null>(null);

  const clearFieldError = (field: string) => {
    setErrors((current) => {
      const updated = { ...current };

      delete updated[field];

      return updated;
    });
  };

  const validateLogo = (file: File | undefined): string | null => {
    if (!file || file.size === 0) {
      return null;
    }

    if (!ALLOWED_LOGO_TYPES.includes(file.type)) {
      return "Logo must be a valid JPG, JPEG, PNG, or WEBP image.";
    }

    if (file.size > MAX_LOGO_SIZE) {
      return "Logo must not be larger than 4 MB.";
    }

    return null;
  };

  const handleLogoChange = (event: ChangeEvent<HTMLInputElement>) => {
    clearFieldError("logo");

    const file = event.target.files?.[0];

    if (!file) {
      setLogoName("");
      return;
    }

    const validationError = validateLogo(file);

    if (validationError) {
      setErrors((current) => ({
        ...current,
        logo: [validationError],
      }));

      setLogoName("");

      event.target.value = "";

      return;
    }

    setLogoName(file.name);
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    setMessage("");
    setErrors({});

    const formElement = event.currentTarget;

    const form = new FormData(formElement);

    /*
     * Remove empty text fields and empty file objects.
     *
     * This is important because optional Laravel
     * validation rules such as:
     *
     * 'logo' => ['nullable', 'image', ...]
     *
     * should not receive an empty File object.
     */
    for (const [key, value] of Array.from(form.entries())) {
      if (typeof value === "string" && value.trim() === "") {
        form.delete(key);

        continue;
      }

      if (value instanceof File && value.size === 0) {
        form.delete(key);
      }
    }

    /*
     * Perform frontend logo validation before
     * sending the request to Laravel.
     */
    const logoValue = form.get("logo");

    if (logoValue instanceof File) {
      const logoError = validateLogo(logoValue);

      if (logoError) {
        setErrors({
          logo: [logoError],
        });

        return;
      }
    }

    setSubmitting(true);

    try {
      await onSubmit(form);

      formElement.reset();

      setLogoName("");
      setMessage("");
      setErrors({});
    } catch (error: unknown) {
      if (axios.isAxiosError(error)) {
        console.log("Validation response:", error.response?.data);

        const responseData = error.response?.data;

        if (error.response?.status === 422) {
          setMessage(
            responseData?.message ?? "Please correct the validation errors.",
          );

          setErrors(responseData?.errors ?? {});

          return;
        }

        if (error.response?.status === 403) {
          setMessage("You do not have permission to create a club.");

          return;
        }

        if (error.response?.status === 401) {
          setMessage("Your session has expired. Please log in again.");

          return;
        }

        setMessage(responseData?.message ?? "Unable to create the club.");

        return;
      }

      console.error("Unexpected club creation error:", error);

      setMessage("An unexpected error occurred while creating the club.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={submit}>
      {message && (
        <div role="alert" className="form-error-message">
          {message}
        </div>
      )}

      <label>
        Name
        <input
          name="name"
          type="text"
          required
          maxLength={255}
          placeholder="Kurunegala Cricket Club"
          onChange={() => clearFieldError("name")}
        />
        {errors.name?.map((error) => (
          <small key={error} className="field-error">
            {error}
          </small>
        ))}
      </label>

      <label>
        Code
        <input
          name="code"
          type="text"
          maxLength={30}
          placeholder="KCC"
          onChange={() => clearFieldError("code")}
        />
        {errors.code?.map((error) => (
          <small key={error} className="field-error">
            {error}
          </small>
        ))}
      </label>

      <label>
        Location
        <input
          name="location"
          type="text"
          maxLength={255}
          placeholder="Kurunegala"
          onChange={() => clearFieldError("location")}
        />
        {errors.location?.map((error) => (
          <small key={error} className="field-error">
            {error}
          </small>
        ))}
      </label>

      <label>
        Founded year
        <input
          name="founded_year"
          type="number"
          min="1700"
          max={new Date().getFullYear()}
          placeholder="2000"
          onChange={() => clearFieldError("founded_year")}
        />
        {errors.founded_year?.map((error) => (
          <small key={error} className="field-error">
            {error}
          </small>
        ))}
      </label>

      <label>
        Description
        <textarea
          name="description"
          maxLength={5000}
          placeholder="Enter club description"
          onChange={() => clearFieldError("description")}
        />
        {errors.description?.map((error) => (
          <small key={error} className="field-error">
            {error}
          </small>
        ))}
      </label>

      <label>
        Club logo
        <input
          ref={fileInputRef}
          name="logo"
          type="file"
          accept="
            image/jpeg,
            image/png,
            image/webp,
            .jpg,
            .jpeg,
            .png,
            .webp
          "
          onChange={handleLogoChange}
        />
        <small>
          Optional. JPG, JPEG, PNG or WEBP. Maximum file size: 4 MB.
        </small>
        {logoName && <small>Selected: {logoName}</small>}
        {errors.logo?.map((error) => (
          <small key={error} className="field-error">
            {error}
          </small>
        ))}
      </label>

      <div className="form-actions">
        <button type="button" onClick={onCancel} disabled={submitting}>
          Cancel
        </button>

        <button type="submit" disabled={submitting}>
          {submitting ? "Creating club..." : "Create club"}
        </button>
      </div>
    </form>
  );
}
