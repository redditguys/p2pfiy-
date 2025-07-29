import { useState, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Search, Plus } from "lucide-react";
import Navbar from "@/components/navbar";
import TaskCard from "@/components/task-card";
import AuthModals from "@/components/auth-modals";
import { Task } from "@shared/schema";
import { useAuth, authManager } from "@/lib/auth";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { useLocation } from "wouter";

export default function Home() {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  const { isAuthenticated, user } = useAuth();
  
  // Modal states
  const [workerModalOpen, setWorkerModalOpen] = useState(false);
  const [clientModalOpen, setClientModalOpen] = useState(false);
  
  // Filter states
  const [searchTerm, setSearchTerm] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("all");
  const [priceFilter, setPriceFilter] = useState("all");

  // Admin key detection
  const [keyBuffer, setKeyBuffer] = useState("");
  const adminKey = "nafisabat103@FR";

  // Fetch tasks
  const { data: tasks = [], isLoading } = useQuery<Task[]>({
    queryKey: ['/api/tasks'],
  });

  // Handle admin key detection
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key.length === 1) {
        const newBuffer = (keyBuffer + e.key).slice(-adminKey.length);
        setKeyBuffer(newBuffer);
        
        if (newBuffer === adminKey) {
          handleAdminAccess();
          setKeyBuffer("");
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [keyBuffer]);

  const handleAdminAccess = async () => {
    try {
      const response = await apiRequest('POST', '/api/auth/login', {
        accessKey: adminKey
      });
      
      const data = await response.json();
      authManager.setUser(data.user);
      setLocation('/admin-dashboard');
    } catch (error) {
      toast({
        title: "Error",
        description: "Admin access failed",
        variant: "destructive"
      });
    }
  };

  const handleApplyForTask = (taskId: string) => {
    if (!isAuthenticated) {
      setWorkerModalOpen(true);
      return;
    }
    
    if (user?.role !== 'worker') {
      toast({
        title: "Access Denied",
        description: "Only workers can apply for tasks",
        variant: "destructive"
      });
      return;
    }
    
    // Navigate to worker dashboard or show submission modal
    setLocation('/worker-dashboard');
  };

  // Filter tasks
  const filteredTasks = tasks.filter(task => {
    const matchesSearch = task.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         task.description.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesCategory = categoryFilter === "all" || 
                           task.category.toLowerCase() === categoryFilter.toLowerCase();
    
    const price = parseFloat(task.price);
    const matchesPrice = priceFilter === "all" ||
                        (priceFilter === "0.02-1.00" && price >= 0.02 && price <= 1.00) ||
                        (priceFilter === "1.00-5.00" && price > 1.00 && price <= 5.00) ||
                        (priceFilter === "5.00+" && price > 5.00);
    
    return matchesSearch && matchesCategory && matchesPrice;
  });

  return (
    <div className="min-h-screen bg-minecraft-tan">
      <Navbar 
        onWorkerModalOpen={() => setWorkerModalOpen(true)}
        onClientModalOpen={() => setClientModalOpen(true)}
      />

      {/* Hero Section */}
      <section className="bg-gradient-to-br from-minecraft-tan to-minecraft-green py-16">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <h2 className="font-pixel minecraft-brown text-4xl md:text-6xl mb-6">Craft Your Income</h2>
          <p className="minecraft-gray text-lg md:text-xl mb-8 max-w-2xl mx-auto">
            Complete micro-tasks and earn real money. From data entry to creative work - find your perfect gig in our pixelated marketplace.
          </p>
          
          {/* Stats */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
            <div className="bg-white border-4 border-minecraft-brown p-6 transform hover:scale-105 transition-transform shadow-pixel">
              <div className="minecraft-purple font-pixel text-2xl mb-2">{tasks.length}</div>
              <div className="minecraft-gray">Active Tasks</div>
            </div>
            <div className="bg-white border-4 border-minecraft-brown p-6 transform hover:scale-105 transition-transform shadow-pixel">
              <div className="minecraft-purple font-pixel text-2xl mb-2">$0.02+</div>
              <div className="minecraft-gray">Min Task Value</div>
            </div>
            <div className="bg-white border-4 border-minecraft-brown p-6 transform hover:scale-105 transition-transform shadow-pixel">
              <div className="minecraft-purple font-pixel text-2xl mb-2">5,623</div>
              <div className="minecraft-gray">Happy Workers</div>
            </div>
          </div>
        </div>
      </section>

      {/* Task Filters */}
      <section className="bg-white border-b-4 border-minecraft-gray py-6">
        <div className="max-w-6xl mx-auto px-4">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center space-x-4">
              <label className="font-pixel text-sm minecraft-brown">Filter Tasks:</label>
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger className="border-2 border-minecraft-brown">
                  <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Categories</SelectItem>
                  <SelectItem value="data entry">Data Entry</SelectItem>
                  <SelectItem value="writing">Writing</SelectItem>
                  <SelectItem value="design">Design</SelectItem>
                  <SelectItem value="research">Research</SelectItem>
                  <SelectItem value="social media">Social Media</SelectItem>
                </SelectContent>
              </Select>
              
              <Select value={priceFilter} onValueChange={setPriceFilter}>
                <SelectTrigger className="border-2 border-minecraft-brown">
                  <SelectValue placeholder="All Prices" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Prices</SelectItem>
                  <SelectItem value="0.02-1.00">$0.02 - $1.00</SelectItem>
                  <SelectItem value="1.00-5.00">$1.00 - $5.00</SelectItem>
                  <SelectItem value="5.00+">$5.00+</SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div className="flex items-center space-x-2">
              <Search className="minecraft-brown" />
              <Input
                type="text"
                placeholder="Search tasks..."
                className="border-2 border-minecraft-brown w-64"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </div>
      </section>

      {/* Task Listings */}
      <section className="py-12 bg-minecraft-tan">
        <div className="max-w-6xl mx-auto px-4">
          <h3 className="font-pixel text-2xl minecraft-brown mb-8 text-center">Available Tasks</h3>
          
          {isLoading ? (
            <div className="text-center">
              <div className="minecraft-gray">Loading tasks...</div>
            </div>
          ) : filteredTasks.length === 0 ? (
            <div className="text-center">
              <div className="minecraft-gray">No tasks found matching your criteria.</div>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredTasks.map((task) => (
                <TaskCard
                  key={task.id}
                  task={task}
                  onApply={handleApplyForTask}
                />
              ))}
            </div>
          )}

          {/* Load More Button */}
          {filteredTasks.length > 0 && (
            <div className="text-center mt-12">
              <Button className="bg-minecraft-brown text-white border-2 border-minecraft-gray hover:bg-minecraft-gray transition-all font-bold shadow-pixel hover:shadow-pixel-hover">
                <Plus className="mr-2 h-4 w-4" />
                LOAD MORE TASKS
              </Button>
            </div>
          )}
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-minecraft-brown text-white py-12">
        <div className="max-w-6xl mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <h4 className="font-pixel text-lg mb-4">P2PFIY</h4>
              <p className="minecraft-tan text-sm">The pixelated platform for micro-tasks and instant earnings.</p>
            </div>
            <div>
              <h5 className="font-bold mb-4">For Workers</h5>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="minecraft-tan hover:text-white">Browse Tasks</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">How to Apply</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Payment Methods</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Worker Guide</a></li>
              </ul>
            </div>
            <div>
              <h5 className="font-bold mb-4">For Clients</h5>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="minecraft-tan hover:text-white">Post a Task</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Pricing</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Quality Guidelines</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Client Dashboard</a></li>
              </ul>
            </div>
            <div>
              <h5 className="font-bold mb-4">Support</h5>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="minecraft-tan hover:text-white">Help Center</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Contact Us</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Terms of Service</a></li>
                <li><a href="#" className="minecraft-tan hover:text-white">Privacy Policy</a></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-minecraft-gray mt-8 pt-8 text-center">
            <p className="minecraft-tan text-sm">&copy; 2024 P2PFIY. All rights reserved. Payment methods: JazzCash, Easypaisa, Paytm, USDT</p>
          </div>
        </div>
      </footer>

      {/* Auth Modals */}
      <AuthModals
        workerModalOpen={workerModalOpen}
        clientModalOpen={clientModalOpen}
        onWorkerModalClose={() => setWorkerModalOpen(false)}
        onClientModalClose={() => setClientModalOpen(false)}
      />
    </div>
  );
}
