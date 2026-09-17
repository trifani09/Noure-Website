export type Pagination = {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
};
export type ImageRef = {
  url: string;
  alt_text: string | null;
  width?: number | null;
  height?: number | null;
};
export type Price = {
  price_amount: number;
  compare_at_amount: number | null;
  currency: string;
};
export type Category = {
  public_id: string;
  name: string;
  slug: string;
  description: string | null;
  image_url: string | null;
  parent?: Pick<Category, "public_id" | "name" | "slug"> | null;
  children?: Category[];
};
export type ProductSummary = {
  public_id: string;
  name: string;
  slug: string;
  short_description: string | null;
  primary_image: ImageRef | null;
  primary_category: Pick<Category, "public_id" | "name" | "slug"> | null;
  price: Price;
  price_range: {
    min_price_amount: number;
    max_price_amount: number;
    currency: string;
  };
  available: boolean;
  published_at: string;
};
export type ProductOption = {
  name: string;
  code: string;
  sort_order: number;
  values: {
    label: string;
    code: string;
    swatch_value: string | null;
    sort_order: number;
  }[];
};
export type ProductVariant = {
  public_id: string;
  sku: string;
  title: string | null;
  selected_options: {
    option_code: string;
    option_name: string;
    value_code: string;
    value_label: string;
  }[];
  price_amount: number;
  compare_at_amount: number | null;
  currency: string;
  available: boolean;
  is_default: boolean;
};
export type ProductDetail = Omit<
  ProductSummary,
  "primary_image" | "primary_category" | "price" | "price_range"
> & {
  description: string | null;
  brand: string | null;
  material?: string | null;
  care_instructions?: string | null;
  shipping_information?: string | null;
  categories: (Pick<Category, "public_id" | "name" | "slug"> & {
    is_primary: boolean;
  })[];
  images: (ImageRef & {
    mime_type: string | null;
    sort_order: number;
    is_primary: boolean;
    variant_public_id: string | null;
  })[];
  options: ProductOption[];
  variants: ProductVariant[];
  created_at: string;
  updated_at: string;
};
export type HomepageBanner = {
  public_id: string;
  placement: string;
  headline: string | null;
  subheading: string | null;
  cta_label: string | null;
  cta_url: string | null;
  desktop_image_url: string;
  mobile_image_url: string | null;
  alt_text: string | null;
};
export type HomepageSection = {
  public_id: string;
  type:
    | "hero_banner"
    | "featured_categories"
    | "featured_products"
    | "promotional_banner"
    | "brand_story";
  name: string;
  sort_order: number;
  configuration: {
    heading?: string;
    body?: string;
    cta_label?: string;
    cta_url?: string;
    placement?: string;
  };
  banners: HomepageBanner[];
  categories: Category[];
  products: ProductSummary[];
};
export type Homepage = {
  hero_banners: HomepageBanner[];
  sections: HomepageSection[];
};
