import axios from 'axios';
 
export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1',
  withCredentials: true,
});
 
api.interceptors.request.use((config) => {
  const token = typeof window !== 'undefined'
    ? localStorage.getItem('token') : null;
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
 
export interface Message {
  id: string;
  channel: 'email' | 'whatsapp';
  status: 'programado'|'encolado'|'enviado'|'entregado'|'leido'|'fallido'|'cancelado';
  recipient_masked: string;
  template_id: number | null;
  sent_at: string | null;
  delivered_at: string | null;
  read_at: string | null;
}
