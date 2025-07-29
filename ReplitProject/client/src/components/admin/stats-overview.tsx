import { Card, CardContent } from "@/components/ui/card";
import { DollarSign, ArrowLeftRight, AlertTriangle, Users, TrendingUp, Clock } from "lucide-react";
import { useQuery } from "@tanstack/react-query";

export default function StatsOverview() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ["/api/admin/stats"],
  });

  if (isLoading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {[...Array(4)].map((_, i) => (
          <Card key={i} className="animate-pulse">
            <CardContent className="p-6">
              <div className="h-16 bg-gray-200 rounded"></div>
            </CardContent>
          </Card>
        ))}
      </div>
    );
  }

  const statCards = [
    {
      title: "Total Revenue",
      value: `$${stats?.totalRevenue || "0.00"}`,
      change: "+12.3% from last month",
      changeType: "positive",
      icon: DollarSign,
      iconBg: "bg-green-100",
      iconColor: "text-green-600",
    },
    {
      title: "Active Transactions",
      value: stats?.activeTransactions || 0,
      change: "Processing",
      changeType: "neutral",
      icon: ArrowLeftRight,
      iconBg: "bg-blue-100",
      iconColor: "text-blue-600",
    },
    {
      title: "Pending Disputes",
      value: stats?.pendingDisputes || 0,
      change: "Needs attention",
      changeType: "warning",
      icon: AlertTriangle,
      iconBg: "bg-amber-100",
      iconColor: "text-amber-600",
    },
    {
      title: "Active Users",
      value: stats?.activeUsers || 0,
      change: "+5.7% this week",
      changeType: "positive",
      icon: Users,
      iconBg: "bg-purple-100",
      iconColor: "text-purple-600",
    },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {statCards.map((stat) => {
        const Icon = stat.icon;
        const ChangeIcon = stat.changeType === "positive" ? TrendingUp : Clock;
        
        return (
          <Card key={stat.title} className="border border-gray-200">
            <CardContent className="p-6">
              <div className="flex items-center">
                <div className={`p-2 ${stat.iconBg} rounded-lg`}>
                  <Icon className={`${stat.iconColor} h-5 w-5`} />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                  <p className="text-2xl font-semibold text-gray-900">{stat.value}</p>
                  <p className={`text-sm mt-1 flex items-center ${
                    stat.changeType === "positive" ? "text-green-600" : 
                    stat.changeType === "warning" ? "text-amber-600" : 
                    "text-blue-600"
                  }`}>
                    <ChangeIcon className="mr-1 h-3 w-3" />
                    {stat.change}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}
