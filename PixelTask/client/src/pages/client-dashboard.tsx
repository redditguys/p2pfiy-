import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Plus, Home, LogOut } from "lucide-react";
import { useAuth, authManager } from "@/lib/auth";
import { useLocation } from "wouter";
import { useEffect } from "react";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Task } from "@shared/schema";

export default function ClientDashboard() {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  const { user, isAuthenticated } = useAuth();
  const [createTaskOpen, setCreateTaskOpen] = useState(false);

  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'client') {
      setLocation('/');
    }
  }, [isAuthenticated, user, setLocation]);

  // Task form state
  const [taskForm, setTaskForm] = useState({
    title: '',
    description: '',
    category: '',
    price: '',
    estimatedTime: '',
    spotsAvailable: 1
  });

  // Fetch client tasks
  const { data: tasks = [], isLoading } = useQuery<Task[]>({
    queryKey: ['/api/tasks/client', user?.id],
    enabled: !!user?.id
  });

  // Create task mutation
  const createTaskMutation = useMutation({
    mutationFn: async (taskData: any) => {
      return apiRequest('POST', '/api/tasks', { ...taskData, clientId: user?.id });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tasks/client', user?.id] });
      setCreateTaskOpen(false);
      setTaskForm({
        title: '',
        description: '',
        category: '',
        price: '',
        estimatedTime: '',
        spotsAvailable: 1
      });
      toast({
        title: "Success",
        description: "Task created successfully"
      });
    },
    onError: (error) => {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to create task",
        variant: "destructive"
      });
    }
  });

  const handleCreateTask = (e: React.FormEvent) => {
    e.preventDefault();
    createTaskMutation.mutate(taskForm);
  };

  const handleLogout = () => {
    authManager.logout();
    setLocation('/');
  };

  if (!isAuthenticated || user?.role !== 'client') {
    return null;
  }

  return (
    <div className="min-h-screen bg-minecraft-tan">
      {/* Header */}
      <div className="bg-minecraft-brown text-white p-4 border-b-4 border-minecraft-gray">
        <div className="flex items-center justify-between">
          <h2 className="font-pixel text-xl">Client Dashboard</h2>
          <div className="flex items-center space-x-4">
            <span className="text-sm">Welcome, {user?.name}</span>
            <Button 
              onClick={() => setLocation('/')}
              variant="outline"
              className="border-white text-white hover:bg-white hover:text-minecraft-brown"
            >
              <Home className="mr-2 h-4 w-4" />
              Home
            </Button>
            <Button 
              onClick={handleLogout}
              className="bg-red-600 text-white border-2 border-white hover:bg-red-700"
            >
              <LogOut className="mr-2 h-4 w-4" />
              Logout
            </Button>
          </div>
        </div>
      </div>

      <div className="max-w-6xl mx-auto p-6">
        {/* Create Task Button */}
        <div className="mb-8">
          <Button
            onClick={() => setCreateTaskOpen(true)}
            className="bg-minecraft-highlight text-white border-2 border-minecraft-purple hover:bg-minecraft-purple shadow-pixel hover:shadow-pixel-hover"
          >
            <Plus className="mr-2 h-4 w-4" />
            Create New Task
          </Button>
        </div>

        {/* Tasks List */}
        <Card className="bg-white border-4 border-minecraft-brown">
          <CardHeader>
            <CardTitle className="font-pixel text-lg minecraft-brown">
              Your Tasks ({tasks.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading ? (
              <p className="minecraft-gray">Loading tasks...</p>
            ) : tasks.length === 0 ? (
              <p className="minecraft-gray">You haven't created any tasks yet.</p>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {tasks.map((task) => (
                  <div key={task.id} className="border-2 border-minecraft-gray p-4 hover:border-minecraft-purple transition-colors">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-xs font-bold minecraft-purple border border-minecraft-purple px-2 py-1">
                        {task.category.toUpperCase()}
                      </span>
                      <span className="font-pixel minecraft-purple text-lg">
                        ${parseFloat(task.price).toFixed(2)}
                      </span>
                    </div>
                    
                    <h4 className="font-bold minecraft-brown mb-2">{task.title}</h4>
                    <p className="text-sm minecraft-gray mb-4">
                      {task.description.substring(0, 100)}...
                    </p>
                    
                    <div className="flex items-center justify-between text-xs minecraft-gray">
                      <span>Status: {task.status}</span>
                      <span>{task.spotsAvailable} spots left</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Create Task Modal */}
      <Dialog open={createTaskOpen} onOpenChange={setCreateTaskOpen}>
        <DialogContent className="bg-white border-4 border-minecraft-brown max-w-2xl shadow-pixel">
          <DialogHeader>
            <DialogTitle className="font-pixel text-lg minecraft-brown">
              Create New Task
            </DialogTitle>
          </DialogHeader>
          
          <form onSubmit={handleCreateTask} className="space-y-4">
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Task Title</Label>
              <Input
                type="text"
                className="w-full border-2 border-minecraft-brown"
                placeholder="Enter task title"
                value={taskForm.title}
                onChange={(e) => setTaskForm({...taskForm, title: e.target.value})}
                required
              />
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Description</Label>
              <Textarea
                className="w-full border-2 border-minecraft-brown"
                placeholder="Describe the task requirements"
                value={taskForm.description}
                onChange={(e) => setTaskForm({...taskForm, description: e.target.value})}
                required
              />
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label className="block text-sm font-bold minecraft-brown mb-2">Category</Label>
                <Select value={taskForm.category} onValueChange={(value) => setTaskForm({...taskForm, category: value})}>
                  <SelectTrigger className="border-2 border-minecraft-brown">
                    <SelectValue placeholder="Select category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="data entry">Data Entry</SelectItem>
                    <SelectItem value="writing">Writing</SelectItem>
                    <SelectItem value="design">Design</SelectItem>
                    <SelectItem value="research">Research</SelectItem>
                    <SelectItem value="social media">Social Media</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div>
                <Label className="block text-sm font-bold minecraft-brown mb-2">Price ($)</Label>
                <Input
                  type="number"
                  step="0.01"
                  min="0.02"
                  className="w-full border-2 border-minecraft-brown"
                  placeholder="0.02"
                  value={taskForm.price}
                  onChange={(e) => setTaskForm({...taskForm, price: e.target.value})}
                  required
                />
              </div>
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label className="block text-sm font-bold minecraft-brown mb-2">Estimated Time</Label>
                <Input
                  type="text"
                  className="w-full border-2 border-minecraft-brown"
                  placeholder="e.g., 2 hours"
                  value={taskForm.estimatedTime}
                  onChange={(e) => setTaskForm({...taskForm, estimatedTime: e.target.value})}
                />
              </div>
              
              <div>
                <Label className="block text-sm font-bold minecraft-brown mb-2">Available Spots</Label>
                <Input
                  type="number"
                  min="1"
                  className="w-full border-2 border-minecraft-brown"
                  value={taskForm.spotsAvailable}
                  onChange={(e) => setTaskForm({...taskForm, spotsAvailable: parseInt(e.target.value)})}
                  required
                />
              </div>
            </div>
            
            <Button 
              type="submit" 
              disabled={createTaskMutation.isPending}
              className="w-full bg-minecraft-green minecraft-brown border-2 border-minecraft-brown hover:bg-green-400 transition-all font-bold"
            >
              {createTaskMutation.isPending ? 'CREATING TASK...' : 'CREATE TASK'}
            </Button>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
