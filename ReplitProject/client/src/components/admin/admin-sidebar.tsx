import { Button } from "@/components/ui/button";
import { 
  Shield, 
  Gauge, 
  CreditCard, 
  ArrowLeftRight, 
  AlertTriangle, 
  Users, 
  Banknote, 
  Settings,
  LogOut 
} from "lucide-react";

interface AdminSidebarProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
  onLogout: () => void;
}

export default function AdminSidebar({ activeTab, onTabChange, onLogout }: AdminSidebarProps) {
  const navigation = [
    { id: "dashboard", label: "Dashboard", icon: Gauge },
    { id: "payments", label: "Payment Management", icon: CreditCard },
    { id: "transactions", label: "Transactions", icon: ArrowLeftRight },
    { id: "disputes", label: "Disputes", icon: AlertTriangle },
    { id: "users", label: "User Management", icon: Users },
    { id: "payouts", label: "Payouts", icon: Banknote },
    { id: "settings", label: "Settings", icon: Settings },
  ];

  return (
    <div className="w-64 bg-white shadow-sm border-r border-gray-200 min-h-screen flex flex-col">
      {/* Header */}
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center">
          <div className="w-8 h-8 bg-brand-500 rounded-lg flex items-center justify-center">
            <Shield className="text-white h-4 w-4" />
          </div>
          <div className="ml-3">
            <h1 className="text-lg font-semibold text-gray-900">PixelTask</h1>
            <p className="text-xs text-gray-500">Admin Panel</p>
          </div>
        </div>
      </div>
      
      {/* Navigation */}
      <nav className="mt-6 px-3 flex-1">
        <div className="space-y-1">
          {navigation.map((item) => {
            const Icon = item.icon;
            const isActive = activeTab === item.id;
            
            return (
              <Button
                key={item.id}
                variant="ghost"
                className={`w-full justify-start text-sm font-medium ${
                  isActive 
                    ? "bg-brand-50 text-brand-700 hover:bg-brand-50" 
                    : "text-gray-700 hover:bg-gray-50"
                }`}
                onClick={() => onTabChange(item.id)}
              >
                <Icon className={`mr-3 h-4 w-4 ${isActive ? "text-brand-500" : "text-gray-400"}`} />
                {item.label}
              </Button>
            );
          })}
        </div>
      </nav>

      {/* User Info & Logout */}
      <div className="p-4 border-t border-gray-200 bg-white">
        <div className="flex items-center mb-3">
          <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
            <Users className="text-gray-600 h-4 w-4" />
          </div>
          <div className="ml-3 flex-1">
            <p className="text-sm font-medium text-gray-900">Admin User</p>
            <p className="text-xs text-gray-500">mathfun103@gmail.com</p>
          </div>
        </div>
        <Button 
          variant="ghost" 
          size="sm" 
          className="w-full justify-start text-gray-400 hover:text-gray-600"
          onClick={onLogout}
        >
          <LogOut className="mr-2 h-4 w-4" />
          Logout
        </Button>
      </div>
    </div>
  );
}
