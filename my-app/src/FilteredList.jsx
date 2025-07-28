import React, { useState } from 'react';
import List from './List';
import Dropdown from 'react-bootstrap/Dropdown';

const types = ["All", "Fruit", "Vegetable"];

function FilteredList({ items }) {
  const [search, setSearch] = useState('');
  const [type, setType] = useState('All');

  const filteredItems = items.filter(item => {
    const matchesType = type === "All" || item.type === type;
    const matchesSearch = item.name.toLowerCase().includes(search.toLowerCase());
    return matchesType && matchesSearch;
  });

  return (
    <div>
      <input
        type="text"
        placeholder="Search"
        className="form-control mb-2"
        value={search}
        onChange={e => setSearch(e.target.value)}
      />
      <Dropdown onSelect={setType}>
        <Dropdown.Toggle variant="success" id="dropdown-basic">
          {type}
        </Dropdown.Toggle>
        <Dropdown.Menu>
          {types.map(t => (
            <Dropdown.Item key={t} eventKey={t}>{t}</Dropdown.Item>
          ))}
        </Dropdown.Menu>
      </Dropdown>
      <List items={filteredItems} />
    </div>
  );
}

export default FilteredList;
