export type Customer = {
  public_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
};

export type ApiValidationError = {
  code: string;
  field?: string;
  message: string;
};
