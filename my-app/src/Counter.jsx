import React, { useState } from 'react';

function Counter() {
  const [count, setCount] = useState(5);

  return (
    <div className="counter">
      <h1>{count}</h1>
      <button className="btn btn-primary" onClick={() => setCount(count + 1)}>
        Increment
      </button>
    </div>
  );
}

export default Counter;
