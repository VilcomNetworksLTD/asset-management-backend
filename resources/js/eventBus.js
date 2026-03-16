// simple event bus for cross-component communication
import mitt from 'mitt';
const emitter = mitt();
export default emitter;