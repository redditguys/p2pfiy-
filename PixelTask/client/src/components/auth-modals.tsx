import { useState } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { X } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { authManager } from "@/lib/auth";
import { useLocation } from "wouter";

interface AuthModalsProps {
  workerModalOpen: boolean;
  clientModalOpen: boolean;
  onWorkerModalClose: () => void;
  onClientModalClose: () => void;
}

export default function AuthModals({
  workerModalOpen,
  clientModalOpen,
  onWorkerModalClose,
  onClientModalClose
}: AuthModalsProps) {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  
  // Worker form state
  const [workerForm, setWorkerForm] = useState({
    accessKey: '',
    name: '',
    email: '',
    skills: [] as string[]
  });

  // Client form state
  const [clientForm, setClientForm] = useState({
    accessKey: '',
    companyName: '',
    email: ''
  });

  // Loading states
  const [workerLoading, setWorkerLoading] = useState(false);
  const [clientLoading, setClientLoading] = useState(false);

  const handleWorkerSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setWorkerLoading(true);

    try {
      const response = await apiRequest('POST', '/api/auth/login', {
        ...workerForm,
        role: 'worker'
      });
      
      const data = await response.json();
      authManager.setUser(data.user);
      
      toast({
        title: "Success!",
        description: "Worker account created successfully"
      });
      
      onWorkerModalClose();
      setLocation('/worker-dashboard');
    } catch (error) {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to create account",
        variant: "destructive"
      });
    } finally {
      setWorkerLoading(false);
    }
  };

  const handleClientSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setClientLoading(true);

    try {
      const response = await apiRequest('POST', '/api/auth/login', {
        ...clientForm,
        name: clientForm.companyName,
        role: 'client'
      });
      
      const data = await response.json();
      authManager.setUser(data.user);
      
      toast({
        title: "Success!",
        description: "Client access granted"
      });
      
      onClientModalClose();
      setLocation('/client-dashboard');
    } catch (error) {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to access client area",
        variant: "destructive"
      });
    } finally {
      setClientLoading(false);
    }
  };

  return (
    <>
      {/* Worker Registration Modal */}
      <Dialog open={workerModalOpen} onOpenChange={onWorkerModalClose}>
        <DialogContent className="bg-white border-4 border-minecraft-brown max-w-md shadow-pixel">
          <DialogHeader>
            <DialogTitle className="font-pixel text-lg minecraft-brown flex items-center justify-between">
              Join as Worker
              <Button
                variant="ghost"
                size="sm"
                onClick={onWorkerModalClose}
                className="minecraft-gray hover:minecraft-brown"
              >
                <X className="h-4 w-4" />
              </Button>
            </DialogTitle>
          </DialogHeader>
          
          <form onSubmit={handleWorkerSubmit} className="space-y-4">
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Access Key</Label>
              <Input
                type="text"
                className="w-full border-2 border-minecraft-brown"
                placeholder="Enter your worker key"
                value={workerForm.accessKey}
                onChange={(e) => setWorkerForm({...workerForm, accessKey: e.target.value})}
                required
              />
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Full Name</Label>
              <Input
                type="text"
                className="w-full border-2 border-minecraft-brown"
                placeholder="Your full name"
                value={workerForm.name}
                onChange={(e) => setWorkerForm({...workerForm, name: e.target.value})}
                required
              />
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Email</Label>
              <Input
                type="email"
                className="w-full border-2 border-minecraft-brown"
                placeholder="your@email.com"
                value={workerForm.email}
                onChange={(e) => setWorkerForm({...workerForm, email: e.target.value})}
                required
              />
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Skills</Label>
              <Textarea
                className="w-full border-2 border-minecraft-brown"
                placeholder="Enter your skills separated by commas"
                value={workerForm.skills.join(', ')}
                onChange={(e) => setWorkerForm({...workerForm, skills: e.target.value.split(',').map(s => s.trim())})}
              />
            </div>
            
            <Button 
              type="submit" 
              disabled={workerLoading}
              className="w-full bg-minecraft-highlight text-white border-2 border-minecraft-purple hover:bg-minecraft-purple transition-all font-bold"
            >
              {workerLoading ? 'CREATING ACCOUNT...' : 'CREATE WORKER ACCOUNT'}
            </Button>
          </form>
        </DialogContent>
      </Dialog>

      {/* Client Registration Modal */}
      <Dialog open={clientModalOpen} onOpenChange={onClientModalClose}>
        <DialogContent className="bg-white border-4 border-minecraft-brown max-w-md shadow-pixel">
          <DialogHeader>
            <DialogTitle className="font-pixel text-lg minecraft-brown flex items-center justify-between">
              Client Access
              <Button
                variant="ghost"
                size="sm"
                onClick={onClientModalClose}
                className="minecraft-gray hover:minecraft-brown"
              >
                <X className="h-4 w-4" />
              </Button>
            </DialogTitle>
          </DialogHeader>
          
          <form onSubmit={handleClientSubmit} className="space-y-4">
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Client Access Key</Label>
              <Input
                type="text"
                className="w-full border-2 border-minecraft-brown"
                placeholder="Enter your client key"
                value={clientForm.accessKey}
                onChange={(e) => setClientForm({...clientForm, accessKey: e.target.value})}
                required
              />
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Company Name</Label>
              <Input
                type="text"
                className="w-full border-2 border-minecraft-brown"
                placeholder="Your company name"
                value={clientForm.companyName}
                onChange={(e) => setClientForm({...clientForm, companyName: e.target.value})}
                required
              />
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Contact Email</Label>
              <Input
                type="email"
                className="w-full border-2 border-minecraft-brown"
                placeholder="contact@company.com"
                value={clientForm.email}
                onChange={(e) => setClientForm({...clientForm, email: e.target.value})}
                required
              />
            </div>
            
            <Button 
              type="submit" 
              disabled={clientLoading}
              className="w-full bg-minecraft-green minecraft-brown border-2 border-minecraft-brown hover:bg-green-400 transition-all font-bold"
            >
              {clientLoading ? 'ACCESSING...' : 'ACCESS CLIENT DASHBOARD'}
            </Button>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
