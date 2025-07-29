import { Button } from "@/components/ui/button";
import { Clock, User } from "lucide-react";
import { Task } from "@shared/schema";

interface TaskCardProps {
  task: Task;
  onApply: (taskId: string) => void;
}

const categoryColors = {
  'data entry': 'bg-minecraft-green minecraft-brown border-minecraft-brown',
  'writing': 'bg-orange-200 text-orange-800 border-orange-800',
  'design': 'bg-purple-200 text-purple-800 border-purple-800',
  'research': 'bg-blue-200 text-blue-800 border-blue-800',
  'social media': 'bg-pink-200 text-pink-800 border-pink-800'
};

export default function TaskCard({ task, onApply }: TaskCardProps) {
  const categoryClass = categoryColors[task.category.toLowerCase() as keyof typeof categoryColors] || 
                       'bg-gray-200 text-gray-800 border-gray-800';

  return (
    <div className="bg-white border-4 border-minecraft-brown p-6 hover:border-minecraft-purple transition-all transform hover:scale-105 shadow-pixel hover:shadow-pixel-hover">
      <div className="flex items-center justify-between mb-4">
        <span className={`px-3 py-1 text-xs font-bold border ${categoryClass}`}>
          {task.category.toUpperCase()}
        </span>
        <span className="font-pixel minecraft-purple text-lg">
          ${parseFloat(task.price).toFixed(2)}
        </span>
      </div>
      
      <h4 className="font-bold minecraft-brown mb-2">{task.title}</h4>
      <p className="text-sm minecraft-gray mb-4">{task.description}</p>
      
      <div className="flex items-center justify-between text-xs minecraft-gray mb-4">
        <span>
          <Clock className="inline w-4 h-4 mr-1" />
          {task.estimatedTime || 'Not specified'}
        </span>
        <span>
          <User className="inline w-4 h-4 mr-1" />
          {task.spotsAvailable} spots left
        </span>
      </div>
      
      <Button 
        onClick={() => onApply(task.id)}
        className="w-full bg-minecraft-highlight text-white border-2 border-minecraft-purple hover:bg-minecraft-purple transition-all font-bold"
        disabled={task.spotsAvailable <= 0}
      >
        {task.spotsAvailable <= 0 ? 'NO SPOTS LEFT' : 'APPLY NOW'}
      </Button>
    </div>
  );
}
