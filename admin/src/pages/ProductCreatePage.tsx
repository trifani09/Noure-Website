import { PageHeader } from '../components/PageHeader'
import { ProductForm } from '../products/ProductForm'
import { createProduct } from '../products/productService'
export function ProductCreatePage() { return <div className="space-y-6"><PageHeader title="Create product" description="Create product content, options, images, and variants in one transaction." /><ProductForm onSave={async (input) => { const response = await createProduct(input); window.location.assign(`/products/${response.data.public_id}`) }} /></div> }
