import { useEffect, useState, useRef } from 'react';
import { useTemplateActions } from 'dashboard/actions/templateActions';
import eventDispatcher from 'builder/store/eventDispatcher';

const requestAnimationFrame = window.requestAnimationFrame
  || window.mozRequestAnimationFrame
  || window.webkitRequestAnimationFrame
  || window.msRequestAnimationFrame;
const cancelAnimationFrame = window.cancelAnimationFrame || window.mozCancelAnimationFrame;

/**
 * @param args
 * @param bargs
 * @returns {boolean}
 */
export const overlaps = (args, bargs) => {
  const isInHorizontalBounds = args.x < bargs.x + bargs.width && args.x + args.width > bargs.x;
  const isInVerticalBounds = args.y < bargs.y + bargs.height && args.y + args.height > bargs.y;
  return isInHorizontalBounds && isInVerticalBounds;
};

/**
 * @param size
 * @param mouseX
 * @param mouseY
 * @returns {number}
 */
const calculateDistance = (size, mouseX, mouseY) => {
  // eslint-disable-next-line no-restricted-properties
  return Math.floor(Math.sqrt(Math.pow(mouseX - (size.x + (size.width / 2)), 2) + Math.pow(mouseY - (size.y + (size.height / 2)), 2)));
};

/**
 * @see https://stackoverflow.com/questions/7322490/finding-element-nearest-to-clicked-point
 *
 * @param args
 * @param ids
 * @param sizes
 * @param id
 */
export const findClosestID = (
  args,
  ids,
  sizes,
  id
) => {
  const distances = [];
  const overlapped = [];
  for (let i = 0; i < ids.length; i++) {
    if (parseInt(ids[i], 10) === id) {
      // eslint-disable-next-line no-continue
      continue;
    }

    const size = sizes[ids[i]];
    if (overlaps(args, size)) {
      const distance = calculateDistance(size, args.clientX, args.clientY);
      distances.push(distance);
      overlapped.push(parseInt(ids[i], 10));
    }
  }

  if (overlapped.length === 0) {
    return null;
  }
  if (overlapped.length === 1) {
    return parseInt(overlapped[0], 10);
  }

  const closestIndex = distances.indexOf(Math.min(...distances));

  return overlapped[closestIndex];
};

let draggingID = -1;
let draggingPID = -1;

const useDragAndDrop = (container, id, type) => {
  const templateActions = useTemplateActions();
  const clone = useRef(null);
  const startY = useRef(0);
  const lastX = useRef(0);
  const lastY = useRef(0);
  const dragX = useRef(0);
  const dragY = useRef(0);
  const topY = useRef(0);
  const bottomY = useRef(0);
  const leftX = useRef(0);
  const rightX = useRef(0);
  const distanceLast = useRef(0);
  const aFrame = useRef(null);
  const mouseDown = useRef(false);
  const dragging = useRef(false);
  const [isDragging, setDragging] = useState(false);
  const mount = document.getElementById('mount');

  /**
   *
   */
  useEffect(() => {
    const handleMouseUp = () => {
      mouseDown.current = false;
      dragging.current = false;
    };

    document.body.addEventListener('mouseup', handleMouseUp, false);

    return () => {
      document.body.removeEventListener('mouseup', handleMouseUp);
    };
  }, []);

  /**
   * @param {MouseEvent} e
   */
  const handleMouseMove = (e) => {
    const { pageX, pageY } = e;

    if (mouseDown.current && !dragging.current) {
      const distanceDelta  = distanceLast.current - pageY;
      if (distanceDelta > 5 || distanceDelta < -5) {
        setDragging(true);
        draggingID = id;
        templateActions.dragging(true);
        dragging.current = true;

        const rect            = container.current.getBoundingClientRect();
        const c               = container.current.cloneNode(true);
        c.style.left          = `${rect.x}px`;
        c.style.top           = `${rect.y}px`;
        c.style.width         = `${container.current.offsetWidth}px`;
        c.style.height        = `${container.current.offsetHeight}px`;
        c.style.position      = 'fixed';
        c.style.pointerEvents = 'none';
        c.style.opacity       = 0.8;

        document.body.style.cursor = 'move';
        document.body.style.userSelect = 'none';
        document.body.appendChild(c);
        clone.current = c;

        startY.current = rect.y;
        lastX.current = e.pageX;
        lastY.current = e.pageY;

        const box = document.getElementById('db-folders-box');
        const boxRect = box.getBoundingClientRect();
        topY.current = boxRect.top;
        bottomY.current = boxRect.bottom;
        leftX.current = boxRect.left;
        rightX.current = boxRect.left + boxRect.width;
      } else {
        return;
      }
    }

    const deltaX  = lastX.current - pageX;
    const deltaY  = lastY.current - pageY;
    lastX.current = pageX;
    lastY.current = pageY;
    dragX.current -= deltaX;
    dragY.current -= deltaY;

    cancelAnimationFrame(aFrame.current);
    aFrame.current = requestAnimationFrame(() => {
      const rect   = clone.current.getBoundingClientRect();
      const top    = startY.current + dragY.current;
      const bottom = startY.current + rect.height + dragY.current;
      if (rect.y  > window.innerHeight - 50) {
        mount.scrollTo({
          top:      mount.scrollTop + 25,
          behavior: 'auto',
        });
      } else if (rect.y <= 50) {
        mount.scrollTo({
          top:      mount.scrollTop - 25,
          behavior: 'auto',
        });
      }

      if (dragY.current < 0 || ((top >= topY.current) && bottom <= bottomY.current)) {
        clone.current.style.transform = `translate3d(0, ${dragY.current}px, 0)`;
      }
    });
  };

  /**
   * @param {MouseEvent} e
   */
  const handleMouseUp = (e) => {
    const { pageX, pageY } = e;

    document.body.removeEventListener('mousemove', handleMouseMove);
    document.body.removeEventListener('mouseup', handleMouseUp);
    mouseDown.current = false;
    dragging.current = false;
    distanceLast.current = 0;

    if (clone.current) {
      clone.current.remove();
      clone.current = null;
      cancelAnimationFrame(aFrame.current);
      document.body.style.cursor     = 'default';
      document.body.style.userSelect = 'default';
      lastX.current                  = 0;
      lastY.current                  = 0;
      dragX.current                  = 0;
      dragY.current                  = 0;
      eventDispatcher.trigger('dropped', { id, type, x: pageX, y: pageY });
      draggingID = -1;
      draggingPID = -1;
      setDragging(false);
      setTimeout(() => {
        templateActions.dragging(false);
      }, 1000);
    }
  };

  /**
   * @param {React.MouseEvent} e
   */
  const handleMouseDown = (e) => {
    if (e.button === 0) {
      draggingPID = parseInt(e.currentTarget.getAttribute('data-pid'), 10) || 0;
      document.body.addEventListener('mouseup', handleMouseUp, false);
      document.body.addEventListener('mousemove', handleMouseMove, false);
      mouseDown.current = true;
      distanceLast.current = e.pageY;
    }
  };

  /**
   * @param {React.MouseEvent} e
   */
  const handleMouseOver = (e) => {
    if (draggingID !== -1) {
      const fid = parseInt(e.currentTarget.getAttribute('data-fid'), 10) || 0;
      const eid = parseInt(e.currentTarget.getAttribute('data-eid'), 10) || 0;
      const pid = parseInt(e.currentTarget.getAttribute('data-pid'), 10) || 0;
      eventDispatcher.trigger('draggingOver', {
        target: {
          fid,
          eid,
          pid,
        },
        source: {
          id:  draggingID,
          pid: draggingPID,
        }
      });
    }
  };

  return [handleMouseDown, handleMouseOver, isDragging];
};

export default useDragAndDrop;
