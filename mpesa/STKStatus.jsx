import React, { useState } from "react";
import axios from "axios";

const STATUS_MESSAGES = {
    "0": { text: "Payment confirmed successfully.", color: "green" },
    "1032": { text: "Request cancelled by user.", color: "yellow" },
    "1037": { text: "Timeout — user did not respond.", color: "yellow" },
    "1": { text: "Payment failed. Insufficient funds or wrong PIN.", color: "red" },
};

export default function STKStatus({ checkoutRequestId: initialId }) {
    const [checkoutId, setCheckoutId] = useState(initialId || "");
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);

    const handleCheck = async (e) => {
        e.preventDefault();
        setLoading(true);
        setResult(null);
        setError(null);

        try {
            const res = await axios.post("/mpesa/stk-status", { id: checkoutId });
            const data = res.data;
            const code = String(data.ResultCode);
            const info = STATUS_MESSAGES[code] || { text: data.ResultDesc || "Unknown status.", color: "gray" };
            setResult({ ...data, ...info });
        } catch (err) {
            setError(err.response?.data?.message || "Could not verify payment status.");
        } finally {
            setLoading(false);
        }
    };

    const colorMap = {
        green: "bg-green-100 text-green-800",
        yellow: "bg-yellow-100 text-yellow-800",
        red: "bg-red-100 text-red-800",
        gray: "bg-gray-100 text-gray-800",
    };

    return (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-md w-full">
            <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                Verify Payment Status
            </h2>

            <form onSubmit={handleCheck} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Checkout Request ID
                    </label>
                    <input
                        type="text"
                        value={checkoutId}
                        onChange={(e) => setCheckoutId(e.target.value)}
                        placeholder="ws_CO_..."
                        required
                        className="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded transition"
                >
                    {loading ? "Checking..." : "Check Status"}
                </button>
            </form>

            {error && (
                <div className="mt-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                    {error}
                </div>
            )}

            {result && (
                <div className={`mt-4 p-3 rounded text-sm ${colorMap[result.color]}`}>
                    <p className="font-medium">{result.text}</p>
                    {result.MpesaReceiptNumber && (
                        <p className="mt-1 text-xs">Receipt: {result.MpesaReceiptNumber}</p>
                    )}
                    {result.Amount && (
                        <p className="text-xs">Amount: KES {result.Amount}</p>
                    )}
                </div>
            )}
        </div>
    );
}
