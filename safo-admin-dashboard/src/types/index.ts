export interface User {
  id: number
  name: string
  phone: string
  email?: string
  type: 'customer' | 'supplier' | 'admin'
  store_name?: string
  city?: string
  is_verified: boolean
  is_active: boolean
  created_at?: string
  supplier?: Supplier
}

export interface Supplier {
  id: number
  user_id: number
  company_name: string
  is_verified: boolean
  is_active: boolean
  rating: number
  total_orders: number
}

export interface Product {
  id: number
  name: string
  slug: string
  price: number
  compare_price?: number
  stock_quantity: number
  is_active: boolean
  is_featured: boolean
  sales_count: number
  rating: number
  category?: Category
  supplier?: Supplier
  created_at: string
}

export interface Category {
  id: number
  name: string
  name_en?: string
  slug: string
  icon?: string
  is_active: boolean
  products_count?: number
  children_count?: number
}

export interface Order {
  id: number
  order_number: string
  status: string
  status_label: string
  total_amount: number
  payment_method: string
  payment_status: string
  customer_name?: string
  supplier_name?: string
  user?: User
  supplier?: Supplier
  items?: OrderItem[]
  created_at: string
}

export interface OrderItem {
  id: number
  product_name: string
  quantity: number
  unit_price: number
  total_price: number
}

export interface DashboardStats {
  users: { total: number; customers: number; suppliers: number }
  suppliers: { total: number; verified: number; pending: number }
  orders: {
    total: number; pending: number; confirmed: number; processing: number
    ready: number; shipped: number; delivered: number; cancelled: number; returned: number
  }
  revenue: { total: number; this_month: number }
  products: { total: number; active: number; low_stock: number }
  recent_orders: Order[]
}

export type OrderStatus = 'pending' | 'confirmed' | 'processing' | 'ready' | 'shipped' | 'delivered' | 'cancelled' | 'returned'
