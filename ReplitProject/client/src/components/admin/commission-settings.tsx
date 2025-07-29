import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Save } from "lucide-react";

export default function CommissionSettings() {
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const { data: settings, isLoading } = useQuery({
    queryKey: ["/api/admin/settings"],
  });

  const [formData, setFormData] = useState({
    commissionRate: "5.0",
    processingFee: "0.30",
    payoutSchedule: "weekly",
  });

  // Update form data when settings load
  useState(() => {
    if (settings) {
      setFormData({
        commissionRate: settings.commissionRate || "5.0",
        processingFee: settings.processingFee || "0.30",
        payoutSchedule: settings.payoutSchedule || "weekly",
      });
    }
  });

  const updateSettingsMutation = useMutation({
    mutationFn: async (data: typeof formData) => {
      const response = await apiRequest("PATCH", "/api/admin/settings", data);
      return response.json();
    },
    onSuccess: () => {
      toast({
        title: "Settings updated",
        description: "Platform settings have been successfully updated",
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/settings"] });
    },
    onError: (error: any) => {
      toast({
        title: "Update failed",
        description: error.message || "Failed to update settings",
        variant: "destructive",
      });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateSettingsMutation.mutate(formData);
  };

  if (isLoading) {
    return (
      <Card>
        <CardContent className="p-6">
          <div className="animate-pulse space-y-4">
            <div className="h-4 bg-gray-200 rounded w-1/3"></div>
            <div className="grid grid-cols-3 gap-4">
              {[...Array(3)].map((_, i) => (
                <div key={i} className="h-16 bg-gray-200 rounded"></div>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg font-semibold">Commission & Fee Management</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div className="space-y-2">
              <Label htmlFor="commissionRate">Platform Commission (%)</Label>
              <div className="relative">
                <Input
                  id="commissionRate"
                  type="number"
                  min="0"
                  max="20"
                  step="0.1"
                  value={formData.commissionRate}
                  onChange={(e) =>
                    setFormData({ ...formData, commissionRate: e.target.value })
                  }
                  className="pr-8"
                />
                <span className="absolute right-3 top-2 text-gray-500 text-sm">%</span>
              </div>
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="processingFee">Processing Fee ($)</Label>
              <div className="relative">
                <span className="absolute left-3 top-2 text-gray-500 text-sm">$</span>
                <Input
                  id="processingFee"
                  type="number"
                  min="0"
                  step="0.01"
                  value={formData.processingFee}
                  onChange={(e) =>
                    setFormData({ ...formData, processingFee: e.target.value })
                  }
                  className="pl-8"
                />
              </div>
            </div>
            
            <div className="space-y-2">
              <Label>Payout Schedule</Label>
              <Select
                value={formData.payoutSchedule}
                onValueChange={(value) =>
                  setFormData({ ...formData, payoutSchedule: value })
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="daily">Daily</SelectItem>
                  <SelectItem value="weekly">Weekly</SelectItem>
                  <SelectItem value="monthly">Monthly</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          
          <div className="flex justify-end">
            <Button 
              type="submit" 
              className="bg-brand-600 hover:bg-brand-700"
              disabled={updateSettingsMutation.isPending}
            >
              <Save className="mr-2 h-4 w-4" />
              {updateSettingsMutation.isPending ? "Saving..." : "Save Settings"}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
