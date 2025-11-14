import { ref, computed } from 'vue'

// Order workflow management
export function useOrderWorkflow() {
    
    // Status definitions and flow
    const statusFlow = {
        received: { 
            next: ['confirmed', 'cancelled'],
            label: 'Received', 
            color: 'orange',
            description: 'New order awaiting confirmation'
        },
        confirmed: { 
            next: ['queued', 'cancelled'],
            label: 'Confirmed', 
            color: 'blue',
            description: 'Order confirmed, entering kitchen queue'
        },
        queued: { 
            next: ['preparing', 'cancelled'],
            label: 'Queued', 
            color: 'indigo',
            description: 'In barista queue awaiting preparation'
        },
        preparing: { 
            next: ['ready', 'queued'],
            label: 'Preparing', 
            color: 'yellow',
            description: 'Currently being prepared'
        },
        ready: { 
            next: ['served', 'preparing'],
            label: 'Ready', 
            color: 'purple',
            description: 'Ready for pickup/delivery'
        },
        served: { 
            next: ['completed', 'ready'],
            label: 'Served', 
            color: 'green',
            description: 'Delivered to table'
        },
        completed: { 
            next: [],
            label: 'Completed', 
            color: 'green',
            description: 'Order fully completed'
        },
        cancelled: { 
            next: [],
            label: 'Cancelled', 
            color: 'red',
            description: 'Order cancelled'
        }
    }

    // Role permissions for status changes
    const rolePermissions = {
        admin: {
            canTransition: {
                'received': ['confirmed', 'cancelled'],
                'served': ['completed'],
                'ready': ['served'], // Admin can also serve orders
                'confirmed': ['cancelled'], // Admin can cancel confirmed orders
                'queued': ['cancelled'],
                'preparing': ['cancelled']
            }
        },
        barista: {
            canTransition: {
                'confirmed': ['queued'], // Auto-transition
                'queued': ['preparing', 'cancelled'],
                'preparing': ['ready', 'queued'], // Can move back to queue if needed
            }
        },
        server: {
            canTransition: {
                'ready': ['served'],
                'served': ['completed']
            }
        }
    }

    // Get status information
    const getStatusInfo = (status) => {
        return statusFlow[status] || { 
            label: status, 
            color: 'gray', 
            description: 'Unknown status'
        }
    }

    // Get available transitions for a role and current status
    const getAvailableTransitions = (currentStatus, role = 'admin') => {
        const permissions = rolePermissions[role]
        if (!permissions || !permissions.canTransition[currentStatus]) {
            return []
        }
        
        return permissions.canTransition[currentStatus].map(status => ({
            status,
            info: getStatusInfo(status)
        }))
    }

    // Validate status transition
    const canTransition = (fromStatus, toStatus, role = 'admin') => {
        const availableTransitions = getAvailableTransitions(fromStatus, role)
        return availableTransitions.some(transition => transition.status === toStatus)
    }

    // Get next automatic status (for workflow automation)
    const getNextAutoStatus = (currentStatus) => {
        if (currentStatus === 'confirmed') {
            return 'queued' // Confirmed orders automatically enter barista queue
        }
        return null
    }

    // Get status color classes for UI
    const getStatusClasses = (status) => {
        const colorMap = {
            received: 'bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 border-orange-200',
            confirmed: 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200',
            queued: 'bg-indigo-100 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-200',
            preparing: 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-200',
            ready: 'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 border-purple-200',
            served: 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-200',
            completed: 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200',
            cancelled: 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200'
        }
        return colorMap[status] || 'bg-gray-100 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border-gray-200'
    }

    // Get status dot classes
    const getStatusDotClasses = (status) => {
        const colorMap = {
            received: 'bg-orange-500',
            confirmed: 'bg-blue-500', 
            queued: 'bg-indigo-500',
            preparing: 'bg-yellow-500',
            ready: 'bg-purple-500',
            served: 'bg-green-500',
            completed: 'bg-green-600',
            cancelled: 'bg-red-500'
        }
        return colorMap[status] || 'bg-gray-500'
    }

    // Get action button configuration for status
    const getActionButtons = (status, role = 'admin') => {
        const transitions = getAvailableTransitions(status, role)
        
        return transitions.map(transition => {
            const actionConfig = {
                received: {
                    confirmed: { icon: 'check_circle', text: 'Confirm Order', variant: 'success' },
                    cancelled: { icon: 'cancel', text: 'Cancel', variant: 'danger' }
                },
                confirmed: {
                    queued: { icon: 'queue', text: 'Send to Queue', variant: 'primary' },
                    cancelled: { icon: 'cancel', text: 'Cancel', variant: 'danger' }
                },
                queued: {
                    preparing: { icon: 'play_arrow', text: 'Start Prep', variant: 'primary' },
                    cancelled: { icon: 'cancel', text: 'Cancel', variant: 'danger' }
                },
                preparing: {
                    ready: { icon: 'task_alt', text: 'Mark Ready', variant: 'success' },
                    queued: { icon: 'queue', text: 'Back to Queue', variant: 'secondary' }
                },
                ready: {
                    served: { icon: 'local_shipping', text: 'Mark Served', variant: 'primary' },
                    preparing: { icon: 'refresh', text: 'Back to Prep', variant: 'secondary' }
                },
                served: {
                    completed: { icon: 'check_circle', text: 'Complete', variant: 'success' },
                    ready: { icon: 'undo', text: 'Back to Ready', variant: 'secondary' }
                }
            }

            const config = actionConfig[status]?.[transition.status] || {
                icon: 'arrow_forward',
                text: transition.info.label,
                variant: 'primary'
            }

            return {
                ...config,
                targetStatus: transition.status,
                statusInfo: transition.info
            }
        })
    }

    // Calculate time in status
    const getTimeInStatus = (statusChangedAt) => {
        if (!statusChangedAt) return null
        
        const now = new Date()
        const statusTime = new Date(statusChangedAt)
        const diffMs = now - statusTime
        const diffMins = Math.floor(diffMs / 60000)
        
        if (diffMins < 1) return 'Just now'
        if (diffMins < 60) return `${diffMins} min`
        
        const diffHours = Math.floor(diffMins / 60)
        const remainingMins = diffMins % 60
        
        if (diffHours < 24) {
            return remainingMins > 0 ? `${diffHours}h ${remainingMins}m` : `${diffHours}h`
        }
        
        const diffDays = Math.floor(diffHours / 24)
        return `${diffDays}d`
    }

    // Check if order is overdue (configurable thresholds)
    const isOverdue = (status, statusChangedAt) => {
        if (!statusChangedAt) return false
        
        const thresholds = {
            received: 5, // 5 minutes to confirm
            confirmed: 2, // 2 minutes to queue
            queued: 10, // 10 minutes in queue
            preparing: 20, // 20 minutes to prepare
            ready: 15, // 15 minutes to serve
            served: 5 // 5 minutes to complete
        }
        
        const threshold = thresholds[status]
        if (!threshold) return false
        
        const now = new Date()
        const statusTime = new Date(statusChangedAt)
        const diffMins = Math.floor((now - statusTime) / 60000)
        
        return diffMins > threshold
    }

    // Update order status via API
    const updateOrderStatus = async (orderId, newStatus) => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            
            const response = await fetch(`/api/orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ status: newStatus })
            })
            
            if (!response.ok) {
                throw new Error(`Failed to update order status: ${response.status}`)
            }
            
            const result = await response.json()
            return result
        } catch (error) {
            console.error('Error updating order status:', error)
            throw error
        }
    }

    // Normalize status to tracker step index (for customer tracker)
    const normalizeStatusToStepIndex = (status) => {
        const s = String(status || '').toLowerCase()
        if (s === 'confirmed') return 0  // Confirmed first
        if (s === 'received') return 1   // In Process second
        if (s === 'queued') return 2     // Queued third
        if (s === 'preparing') return 3  // Preparation fourth
        if (s === 'ready') return 4      // Ready fifth
        return -1
    }

    // Check if status is terminal (served, completed, cancelled)
    const isTerminalStatus = (status) => {
        const s = String(status || '').toLowerCase()
        return ['served', 'completed', 'cancelled'].includes(s)
    }

    // Estimate order completion time
    const estimateEta = (status, createdAt) => {
        // Simple baseline per status in minutes
        const baselines = { 
            received: 20, 
            confirmed: 18, 
            queued: 15, 
            preparing: 8, 
            ready: 2 
        }
        const base = baselines[String(status || '').toLowerCase()] ?? 15
        const startMs = createdAt ? new Date(createdAt).getTime() : Date.now()
        return new Date(startMs + base * 60000)
    }

    // Format ETA for display
    const formatEta = (etaDate) => {
        if (!etaDate) return ''
        
        const now = new Date()
        const diffMs = etaDate - now
        const diffMins = Math.floor(diffMs / 60000)
        
        // Format time
        const timeStr = etaDate.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        })
        
        // Add relative time
        if (diffMins <= 0) {
            return `${timeStr} (ready now)`
        } else if (diffMins < 60) {
            return `${timeStr} (about ${diffMins}m)`
        } else {
            const hours = Math.floor(diffMins / 60)
            const remainingMins = diffMins % 60
            const relativeStr = remainingMins > 0 ? `${hours}h ${remainingMins}m` : `${hours}h`
            return `${timeStr} (about ${relativeStr})`
        }
    }

    return {
        // Status definitions
        statusFlow,
        rolePermissions,
        
        // Status utilities
        getStatusInfo,
        getAvailableTransitions,
        canTransition,
        getNextAutoStatus,
        
        // UI utilities
        getStatusClasses,
        getStatusDotClasses,
        getActionButtons,
        
        // Time utilities
        getTimeInStatus,
        isOverdue,
        estimateEta,
        formatEta,
        
        // API utilities
        updateOrderStatus,
        
        // Customer tracker utilities
        normalizeStatusToStepIndex,
        isTerminalStatus
    }
}
