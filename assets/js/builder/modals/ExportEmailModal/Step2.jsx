import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

const Step2 = ({ images, transferCount }) => {
  return (
    <>
      <div className="pb-3 text-center">
        Transferring images.
      </div>
      <div className="text-center">
        <div className="fancybox-loading fancybox-loading-inline mb-2" />
        <div id="export-lightbox-transfer-image-count">
          {transferCount} of {images.length} images
        </div>
      </div>
    </>
  );
};

Step2.propTypes = {
  images:        PropTypes.array.isRequired,
  transferCount: PropTypes.number.isRequired
};

const mapStateToProps = state => ({
  transferCount: state.source.transferCount
});

export default connect(mapStateToProps)(Step2);
