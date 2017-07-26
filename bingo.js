
var dimension = 11;
var alias = '???';
var is_host = false;


function webSocket(app) {
  var wsServer = 'ws://192.168.1.135:9502';
  var ws = new WebSocket(wsServer);
  ws.onopen = function (evt) {
    console.log("Connected to WebSocket server.");
  };

  ws.onclose = function (evt) {
    console.log("Disconnected");
    setTimeout(() => { webSocket(app);}, 3000)
  };

  ws.onmessage = function (evt) {
    // console.log(evt);
    app.handleWsMessage(evt);
  }.bind(app);

  ws.onerror = function (evt, e) {
    console.log("Error occured: " + evt.data);
  };

  function send(txt) {
    var id = $('#id').val();
    var arr = {
      "id": id,
      "txt": txt
    };

    ws.send(JSON.stringify(arr));
  }
}

class App extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      pick: new Array(dimension * dimension).fill(0),
      picked: [],
      result: [],
      nxtNum: 1,
      hits: null,
      hit_pool: this.generateNums(dimension * dimension),
      hit_step: 0,
      dimension: dimension
    };

    this.handleClick = this.handleClick.bind(this);
    this.handleClear = this.handleClear.bind(this);
    this.handleGo = this.handleGo.bind(this);
    this.handleAdd = this.handleAdd.bind(this);
    this.handleRandom = this.handleRandom.bind(this);
    this.handleWsMessage = this.handleWsMessage.bind(this);

    webSocket(this);
  }
  render() {
    const results = this.state.picked.map((p) => <Board nums={p} hits={this.state.hits} hit_lines={this.checkLine(p)} />);

    return (
      <div>
        <h1>Bingo (Playe: {alias})</h1>
        <button onClick={() => this.handleRandom()}>random</button>
        {is_host && <button onClick={() => this.handleClear()}>Clear</button>}
        <button onClick={() => this.handleAdd()}>Add</button>
        <h1> Pick Numbers, next: {this.state.nxtNum}</h1>
        <Board nums={this.state.pick} handle={this.handleClick} />
        <br />
        {is_host && <button onClick={() => this.handleGo()}>go</button>}
        <h1> Result {(this.state.hits) ? this.state.hits.toString() : ''}</h1>
        {results}
        <pre>
          pick: {this.state.pick}<br/>
          picked: {this.state.picked}<br/>
          result: {this.state.result}<br/>
          nxtNum: {this.state.nxtNum}<br/>
          hits: {this.state.hits}<br/>
          hit_pool: {this.state.hit_pool}<br/>
          hit_step: {this.state.hit_step}<br/>
          dimension: {this.state.dimension}
        </pre>
      </div>
    );
  }
  
  handleWsMessage(evt){
    if (null != evt.data && evt.data != 'hello, welcome' && evt.data != '??? connected.') {
      if (evt.data == 'clear') {
        this.handleClear();
      } else {
        this.setState({
          hits: JSON.parse(evt.data),
        });
      }
    }
  }

  handleClick(i) {
    if (this.state.pick[i] == 0) {
      let newNums = this.state.pick.slice(0);
      newNums[i] = this.state.nxtNum;
      this.setState({
        pick: newNums,
        nxtNum: this.state.nxtNum + 1
      });
    }
  }

  handleGo() {
    const hit_pool = this.state.hit_pool;
    const hits = hit_pool.slice(0, this.state.hit_step + 1);
    this.setState({
      hits: hits,
      hit_step: this.state.hit_step + 1
    });
  }

  handleRandom() {
    const ranNums = this.generateNums(dimension * dimension);
    this.setState({
      pick: ranNums,
      result: ranNums,
    });
  }

  handleClear() {
    this.setState({
      pick: new Array(dimension * dimension).fill(0),
      picked: [],
      result: [],
      nxtNum: 1,
      hits: null,
      hit_pool: this.generateNums(dimension * dimension),
      hit_step: 0
    });
  }

  handleAdd() {
    const pick = this.state.pick;
    let picked = this.state.picked;
    picked.push(pick)
    this.setState({
      picked: picked,
      pick: new Array(dimension * dimension).fill(0),
      nxtNum: 1,
    });
  }

  generateNums(max) {
    const pool = Array.apply(null, Array(max + 1)).map(function (_, i) { return i; });
    let arr = [];
    while (arr.length < max) {
      const idx = parseInt(Math.random() * (max + 1));
      if (pool[idx] != 0) {
        arr.push(pool[idx]);
        pool[idx] = 0;
      }
    }
    return arr;
  }

  checkLine(arr) {
    const lines = [];
    let slash1 = [];
    let slash2 = [];
    for (var i = 0; i < dimension; i++) {
      let row = [];
      let col = [];

      for (var o = 0; o < dimension; o++) {
        row.push(o + dimension * i);
        col.push(o * dimension + i);
      }
      lines.push(row);
      lines.push(col);

      slash1.push(i + dimension * i);
      slash2.push((dimension - 1) * (i + 1));
    }
    lines.push(slash1);
    lines.push(slash2);

    const hits = this.state.hits || [];
    let rtn = [];
    for (var i = 0; i < lines.length; i++) {
      let line_is_hit = true;
      for (var num in lines[i]) {
        if (undefined == arr[lines[i][num]] || !hits.includes(arr[lines[i][num]])) {
          line_is_hit = false;
          break;
        }
      }

      if (line_is_hit) {
        for (var num in lines[i]) {
          rtn.push(lines[i][num]);
        }
      }

    }
    return rtn;
  }
}

class Board extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const num_tiles = this.props.nums.map((n, i) =>
      <Btn active={((this.props.hits || []).includes(n))}
        lined={((this.props.hit_lines || []).includes(i))}
        idx={i} num={n}
        handle={this.props.handle}
      />
    );
    return (
      <div style={{ width: 46 * dimension, height: 46 * dimension }} className="group">
        {num_tiles}
      </div>
    );
  }

}

class Btn extends React.Component {
  constructor(props) {
    super(props);

    if (undefined != this.props.handle)
      this.handleClick = this.props.handle.bind(this);
    else
      this.handleClick = () => { };
  }
  render() {
    let status = '';
    if (this.props.active == true) {
      status = 'active';
    }
    if (this.props.lined == true) {
      status = 'lined';
    }
    return (
      <div className={"btn " + status}
        onClick={(e) => this.handleClick(this.props.idx)}
      >
        {(this.props.num != 0) ? this.props.num : <span className="black">&nbsp;</span>}
      </div>
    );
  }
}

class Login extends React.Component {
  constructor() {
    super()
  }
  render() {
    return (
      <div>
        <h1>Login</h1>
        <input id="alias" />
        <input id="submit" type="button" onClick={this.handleSubmit} value="Enter" />
      </div>
    )
  }

  handleSubmit() {
    alias = document.getElementById('alias').value;
    ReactDOM.render(
      <App />,
      document.getElementById('root')
    );
  }
}

ReactDOM.render(
  <Login />,
  document.getElementById('root')
);
