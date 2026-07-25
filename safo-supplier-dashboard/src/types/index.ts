export interface User {
  id: number
  name: string
  phone: string
  type: 'customer' | 'supplier' | 'admin'
  store_name?: string
  city?: string
  is_verified: boolean
}

export interface Product {
  id: number
  name: string
  name_en?: string
  slug: string
  description?: string
  price: number
  compare_price?: number
  discount_percent: number
  unit: string
  unit_quantity: number
  min_order_quantity: number
  stock_quantity: number
  is_low_stock: boolean
  is_out_of_stock: boolean
  images?: string[]
  thumbnail?: string
  is_active: boolean
  is_featured: boolean
  views_count: number
  sales_count: number
  rating: number
  total_ratings: number
  category?: Category
  created_at: string
}

export interface Category {
  id: number
  name: string
  name_en?: string
  slug: string
  icon?: string
  children_count?: number
  products_count?: number
}

export interface Order {
  id: number
  order_number: string
  status: OrderStatus
  status_label: string
  subtotal: number
  delivery_fee: number
  discount_amount: number
  total_amount: number
  payment_method: string
  payment_status: string
  delivery_address: string
  delivery_notes?: string
  items_count: number
  user?: User
  items?: OrderItem[]
  status_history?: StatusHistory[]
  confirmed_at?: string
  shipped_at?: string
  delivered_at?: string
  created_at: string
}

export interface OrderItem {
  id: number
  product_id: number
  product_name: string
  product_image?: string
  product_unit: string
  quantity: number
  unit_price: number
  total_price: number
}

export interface StatusHistory {
  from_status: string | null
  to_status: string
  note?: string
  changed_by?: { id: number; name: string; type: string }
  created_at: string
}

export type OrderStatus = 'pending' | 'confirmed' | 'processing' | 'ready' | 'shipped' | 'delivered' | 'cancelled' | 'returned'

export interface DashboardStats {
  orders: {
    total: number
    pending: number
    confirmed: number
    processing: number
    ready: number
    shipped: number
    delivered: number
    cancelled: number
    today: number
  }
  revenue: {
    total: number
    this_month: number
  }
  products: {
    total: number
    active: number
    low_stock: number
  }
  recent_orders: Array<{
    id: number
    order_number: string
    customer_name: string
    status: OrderStatus
    total_amount: number
    items_count: number
    created_at: string
  }>
  top_products: Array<{
    id: number
    name: string
    price: number
    sales_count: number
    stock_quantity: number
  }>
}

export interface ApiResponse<T> {
  success: boolean
  message?: string
  data: T
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
