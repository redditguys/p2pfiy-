import { useState } from "react";
import { useLocation } from "wouter";
import AdminSidebar from "@/components/admin/admin-sidebar";
import StatsOverview from "@/components/admin/stats-overview";
import TransactionTable from "@/components/admin/transaction-table";
import UserActivity from "@/components/admin/user-activity";
import PayoutQueue from "@/components/admin/payout-queue";
import CommissionSettings from "@/components/admin/commission-settings";
import { Button } from "@/components/ui/button";
import { Bell, Download } from "lucide-react";

export default function AdminDashboard() {
  const [, setLocation] = useLocation();
  const [activeTab, setActiveTab] = useState("dashboard");

  const handleLogout = () => {
    setLocation("/admin/login");
  };

  const handleExportData = () => {
    // In a real app, this would trigger data export
    console.log("Exporting data...");
  };

  return (
    <div className="min-h-screen bg-gray-50 flex">
      <AdminSidebar 
        activeTab={activeTab} 
        onTabChange={setActiveTab}
        onLogout={handleLogout}
      />
      
      <div className="flex-1 overflow-hidden">
        {/* Top Header */}
        <header className="bg-white shadow-sm border-b border-gray-200">
          <div className="px-6 py-4">
            <div className="flex items-center justify-between">
              <h1 className="text-2xl font-semibold text-gray-900">
                Payment Management Dashboard
              </h1>
              <div className="flex items-center space-x-4">
                <div className="relative">
                  <Button variant="ghost" size="sm" className="p-2">
                    <Bell className="h-5 w-5" />
                    <span className="absolute -top-1 -right-1 h-4 w-4 bg-red-500 rounded-full flex items-center justify-center text-xs text-white">
                      3
                    </span>
                  </Button>
                </div>
                <Button onClick={handleExportData} className="bg-brand-600 hover:bg-brand-700">
                  <Download className="mr-2 h-4 w-4" />
                  Export Data
                </Button>
              </div>
            </div>
          </div>
        </header>

        {/* Main Content */}
        <main className="flex-1 overflow-y-auto p-6">
          {activeTab === "dashboard" && (
            <>
              <StatsOverview />
              <div className="mt-8">
                <TransactionTable />
              </div>
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                <UserActivity />
                <PayoutQueue />
              </div>
              <div className="mt-8">
                <CommissionSettings />
              </div>
            </>
          )}
          
          {activeTab === "payments" && (
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-6">Payment Management</h2>
              <TransactionTable />
            </div>
          )}
          
          {activeTab === "transactions" && (
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-6">All Transactions</h2>
              <TransactionTable />
            </div>
          )}
          
          {activeTab === "disputes" && (
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-6">Dispute Management</h2>
              <p className="text-gray-600">Dispute management interface coming soon...</p>
            </div>
          )}
          
          {activeTab === "users" && (
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-6">User Management</h2>
              <UserActivity />
            </div>
          )}
          
          {activeTab === "payouts" && (
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-6">Payout Management</h2>
              <PayoutQueue />
            </div>
          )}
          
          {activeTab === "settings" && (
            <div>
              <h2 className="text-xl font-semibold text-gray-900 mb-6">Platform Settings</h2>
              <CommissionSettings />
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
