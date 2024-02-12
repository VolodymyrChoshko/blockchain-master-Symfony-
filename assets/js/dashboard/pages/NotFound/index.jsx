import React from 'react';
import Box from 'dashboard/components/Box';

const NotFound = () => {
  return (
    <Box className="mt-4 mb-4 text-center" narrow white>
      <h2>Not Found</h2>
      <p>
        The page you&apos;re looking for could not be found.
      </p>
    </Box>
  );
};

export default NotFound;
