import React from 'react';
import axios from 'axios';
import './App.css';

export default class MainApplication extends React.Component {

  constructor(props) {
    super(props);
    this.state = {
      days: 7,
      products: [],
      auditTrail: []
    }
  }

  async componentDidMount() {
    await this._loadData();
  }

  _loadData = async () => {
    var responseData = { data: { products: [], auditTrail: [] } };
    await axios.get(`${process.env.REACT_APP_API_URL}backend/src/api/SimulateSevenDays.php?days=${this.state.days}`)
      .then(function (response) {
        responseData = response;
      })
      .catch(function (response) {
        console.log(response);
      })
      .finally(function () { });
    this.setState({
      products: responseData.data.products,
      auditTrail: responseData.data.auditTrail
    })
  }

  _OnDaysChange = async (ev) => {
    console.log(ev.target.value)
    await this.setState({ days: ev.target.value }, async () => { await this._loadData(); });

  }

  render() {
    let { days } = this.state;
    return (
      <div className="App">
        <div className="intro-container">
          <h1>Inventory Code Challenge</h1>
          <h2>Enter days to process</h2>
          <input id="days-to-process" min="0" type="number" value={days} onChange={this._OnDaysChange} />
          <p className={`subtitle ${days>7?'show':'hide'}`}>Configured data is only up to 7 days. <br/>Update the orders-sample.json to check this data.</p>
          <span>gdjsl</span>
        </div>
        <div className="main-container">
          <div className="container">
            <h1>Inventory Dashboard</h1>
            <div className="item-list">
              {
                this.state.products.map((item, index) => (
                  <div className="item" key={`unique-key-item-${index}`}>
                    <div className="header">
                      <h1>{item.name.replace('_', ' ')}</h1>
                    </div>
                    <div className="body">
                      <div className="item-detail">
                        <h2>Units Sold</h2>
                        <span>{item.unitsSold}</span>
                      </div>
                      <div className="item-detail">
                        <h2>Units Left</h2>
                        <span>{item.currentStockLevel}</span>
                      </div>
                    </div>
                    <div className="hover-container">
                    <div className="header">
                      <h1>{item.name.replace('_', ' ')}</h1>
                    </div>
                    <div className="body">
                      <div className="item-detail">
                        <h2>Units Sold</h2>
                        <span>{item.unitsSold}</span>
                      </div>
                      <div className="item-detail">
                        <h2>Units Left</h2>
                        <span>{item.currentStockLevel}</span>
                      </div>
                      <div className="item-detail">
                        <h2>Purchased and Received</h2>
                        <span>{item.purchasedAndReceived}</span>
                      </div>
                      <div className="item-detail">
                        <h2>Purchased and Pending</h2>
                        <span>{item.purchasedAndPending}</span>
                        <span>{Math.abs(item.poCreatedStatus)>0?`To be delivered in ${Math.abs(item.poCreatedStatus)} day${Math.abs(item.poCreatedStatus)>1?'s':''}`:'To be delivered today'}</span>
                      </div>
                    </div>
                    </div>
                  </div>))
              }
            </div>
          </div>
        </div>
      </div>
    );
  }
};
