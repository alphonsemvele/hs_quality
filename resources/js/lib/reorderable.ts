import { useCallback, useState } from 'react';

interface UseReorderableResult {
    draggedId: string | null;
    overId: string | null;
    /** Handlers to spread on each draggable item */
    bindItem: (id: string) => {
        draggable: true;
        onDragStart: (e: React.DragEvent) => void;
        onDragOver: (e: React.DragEvent) => void;
        onDragLeave: (e: React.DragEvent) => void;
        onDrop: (e: React.DragEvent) => void;
        onDragEnd: () => void;
    };
}

/**
 * HTML5 drag-and-drop hook with keyboard alternative recommended for a11y —
 * consumers should also expose Move up / Move down buttons (we render them
 * next to the drag handle in the integration).
 *
 * The hook is intentionally minimal: it tracks which item is being dragged
 * and which is hovered, and calls `onReorder(fromId, toId)` when the user
 * drops. The host owns the actual array mutation and the optimistic UI.
 */
export function useReorderable(onReorder: (fromId: string, toId: string) => void): UseReorderableResult {
    const [draggedId, setDraggedId] = useState<string | null>(null);
    const [overId, setOverId] = useState<string | null>(null);

    const bindItem = useCallback(
        (id: string) => ({
            draggable: true as const,
            onDragStart: (e: React.DragEvent) => {
                setDraggedId(id);
                // Required for Firefox to fire dragstart properly
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', id);
            },
            onDragOver: (e: React.DragEvent) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (overId !== id) setOverId(id);
            },
            onDragLeave: () => {
                if (overId === id) setOverId(null);
            },
            onDrop: (e: React.DragEvent) => {
                e.preventDefault();
                const fromId = e.dataTransfer.getData('text/plain') || draggedId;
                if (fromId && fromId !== id) {
                    onReorder(fromId, id);
                }
                setDraggedId(null);
                setOverId(null);
            },
            onDragEnd: () => {
                setDraggedId(null);
                setOverId(null);
            },
        }),
        [draggedId, overId, onReorder],
    );

    return { draggedId, overId, bindItem };
}

/**
 * Pure helper: produce a new array with `fromIdx` moved to before `toIdx`.
 */
export function moveItem<T>(items: T[], fromIdx: number, toIdx: number): T[] {
    if (fromIdx === toIdx) return items;
    const next = items.slice();
    const [removed] = next.splice(fromIdx, 1);
    next.splice(toIdx, 0, removed);
    return next;
}
