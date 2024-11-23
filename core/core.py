from flask import Flask, request, jsonify
import logging

app = Flask(__name__)
app.testing = True

logging.basicConfig(level=logging.DEBUG, filename='core.log', filemode='w',
                    format='%(name)s - %(levelname)s - %(message)s')

@app.route('/test', methods=['GET'])
def test():
    return jsonify({
        'test': []
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5092, debug=True)